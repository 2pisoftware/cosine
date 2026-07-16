<script setup lang="ts">
import * as s3 from "./multipart.js";
import { ref, computed } from "../../../../templates/base/node_modules/vue";

import LocalFilePreview from "./LocalFilePreview.vue";

const props = defineProps<{
    endpoint: string;
    calculateHash?: boolean;
}>();

const endpoint = computed(() => props.endpoint.length ? props.endpoint : undefined);

const upload = async (e: SubmitEvent) => {
    const target = e.target as HTMLFormElement;

    // for some reason, when no files are selected, this returns a [File] with 0 size
    // I believe vue is somehow messing with it, but I have no idea why
    files.value = new FormData(target).getAll("files") as File[];

    files.value = files.value.filter(x => x.size);

    if (!files.value.length) return;

    done.value = false;
    uploading.value = true;
    failed_count.value = 0;
    success_count.value = 0;
    progress.value = 0;

    disableUpload.value = true;
    disableSubmit.value = true;

    is_initialising.value = true;

    let uploadedChunks = 0;
    for (const file of files.value) {
        let upload_id: string | undefined = undefined;
        try {
            upload_id = await s3.beginMultipartUpload(
                file,
                file.name,
                endpoint.value,
                props.calculateHash ?? true
            );

            is_initialising.value = false;

            await s3.uploadParts(file, upload_id, (chunk) => progress.value = chunk + uploadedChunks);

            await s3.completeUpload(upload_id);

            success_count.value++;
        }
        catch (e) {
            if (upload_id)
                await s3.abortUpload(upload_id).catch(() => { });
            failed_count.value++;
        }

        uploadedChunks += Math.ceil(file.size / s3.CHUNK_SIZE);
    }

    progress.value = total_progress.value;
    disableUpload.value = false;
    done.value = true;

    // This is kind of awful, sorry
    if (failed_count.value === 0) {
        //@ts-ignore
        cmfiveEventBus
            .dispatchEvent(new CustomEvent("multipart-upload-success", {
                detail: {
                    files: files.value,
                }
            }));
    }
};

const updateFilePreview = (event: ChangeEvent<HTMLInputElement>) => {
    disableSubmit.value = false;
    files.value = [...event.target.files];
};

type FileWithProgress = File & {
    progress?: number;
    done?: boolean;
};

// can you tell I don't enjoy state
const files = ref<FileWithProgress[]>([]);
const uploading = ref(false);
const done = ref(false);
const failed_count = ref(0);
const success_count = ref(0);
const progress = ref(0);
const total_progress = computed(() => files.value.reduce((acc, curr) => acc + Math.ceil(curr.size / s3.CHUNK_SIZE), 0));
const disableSubmit = ref(true);
const disableUpload = ref(false);
const is_initialising = ref(false);
</script>

<template>
    <div class="panel">
        <div v-if="uploading">
            <div class="progress-stacked mb-2">
                <div class="progress" role="progressbar" :aria-valuenow="is_initialising ? 1 : 0" aria-valuemin="0"
                    aria-valuemax="1" :style="`width: ${is_initialising ? 100 : 0}%`">
                    <div class="progress-bar bg-info progress-bar-striped progress-bar-animated">Initialising</div>
                </div>

                <div class="progress" role="progressbar" :aria-valuenow="failed_count" aria-valuemin="0"
                    :aria-valuemax="(files.size)" :style="`width: ${(failed_count / files.length) * 100}%;`">
                    <div class="progress-bar bg-danger"></div>
                </div>

                <div class="progress" role="progressbar" :aria-valuenow="progress" aria-valuemin="0"
                    :aria-valuemax="(total_progress)"
                    :style="`width: ${((progress / total_progress) - (failed_count / files.length)) * 100}%;`">
                    <div class="progress-bar progress-bar-striped"></div>
                </div>
            </div>
            <p>Uploading {{ success_count + failed_count }} / {{ files.length }}</p>
            <p v-if="failed_count">Failed: {{ failed_count }}</p>

            <p v-if="done">Finished uploading</p>
        </div>

        <form @submit.prevent="upload">
            <fieldset id="multipart_uploader_fieldset" class="d-flex gap-2 align-items-center pt-0 shadow-none">
                <label for="multipart_uploader_files" :class="disableUpload ? 'opacity-50' : ''"
                    style="cursor: pointer">
                    <p class="mb-0 form-control">Select Files <i class="bi bi-cloud-arrow-up"></i></p>
                </label>
                <input id="multipart_uploader_files" :disabled="disableUpload" @change="updateFilePreview" name="files"
                    type="file" multiple hidden>
                <button id="multipart_uploader_submit" :disabled="disableSubmit" type="submit" class="btn btn-primary">
                    Upload
                </button>
            </fieldset>
        </form>

        <div v-if="files.length">
            <h5 class="mt-2 mb-3 pb-2 border-bottom">Selected</h5>
            <div class="d-flex gap-1 flex-wrap">
                <LocalFilePreview :file="file" v-for="file in files.slice(0, 5)" style="max-height: 200px;">
                </LocalFilePreview>
            </div>
            <p v-if="files.length > 5" class="d-flex justify-content-center align-items-center">Additional {{
                files.length - 5 }} files not previewed.</p>
        </div>
    </div>
</template>
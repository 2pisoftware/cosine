import { ArrayBuffer as spark } from "~/spark-md5";

const beginMultipartUpload = async (
    file: File,
    filename: string = file.name,
    endpoint = "/file/ajax_multipart",
    calculateHash = true
): Promise<string> => {
    const res = await fetch(endpoint, {
        method: "POST",
        body: JSON.stringify({
            filename,
            mime: file.type,
            size: file.size,
            md5: calculateHash ? await getFileMd5(file) : null,
        })
    });

    const json = await res.json();

    return json.id;
};

const upload_part = async (
    data: number[],
    upload_id: string,
    part_number: number,
    signal: AbortSignal,
    progress = (sent: number, total: number) => { }
) => {
    if (signal.aborted) throw new Error("aborted");

    const md5 = btoa(spark.hash(data, true));

    const res = await fetch("/file-multipart/ajax_part", {
        method: "POST",
        body: JSON.stringify({
            id: upload_id,
            part: part_number,
            length: data.length,
            md5,
        }),
        signal
    });

    const { endpoint } = await res.json();

    if (!res.ok) throw new Error("failed to get endpoint");

    const xhr = new XMLHttpRequest();

    await new Promise<void>((resolve, reject) => {
        xhr.upload.addEventListener("progress", (event) => {
            if (!event.lengthComputable) return;

            progress(event.loaded, event.total);
        });

        xhr.addEventListener("loadend", () => {
            if (xhr.readyState !== 4) reject(new Error("Failed upload"));

            if (xhr.status !== 200) reject(new Error(`Failed upload with status ${xhr.status}: ${xhr.responseText}`));

            resolve();
        })

        signal.addEventListener("abort", () => {
            xhr.abort();
            reject("Upload aborted");
        })

        xhr.open("PUT", endpoint, true);
        xhr.setRequestHeader("Content-MD5", md5);
        xhr.send(new Uint8Array(data));
    })
};

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));
const ATTEMPT_DELAY_SECONDS = 5;

const CHUNK_SIZE = 1024 * 1024 * 5;

const uploadParts = async (
    file: File,
    upload_id: string,
    progress = (currentChunk: number) => { }
) => {
    const MAX_RETRIES = 5;

    let i = 1;
    let part: number[] = [];

    let progresses: Map<number, number> = new Map();
    const notifyProgress = (i: number, done: number) => {
        progresses.set(i, done);
        const totalProgress = [...progresses.values()].reduce((prev, curr) => prev + curr);
        progress(totalProgress);
    }


    let promises: Promise<void>[] = [];

    const abortController = new AbortController();

    const doPartWithRetry = async (part: number[], part_number: number) => {
        if (abortController.signal.aborted) return;

        let attempts = 0;
        do {
            try {
                attempts++;
                await upload_part(
                    part,
                    upload_id,
                    part_number,
                    abortController.signal,
                    (sent, total) => notifyProgress(part_number, sent / total)
                );

                notifyProgress(part_number, 1);
                return;
            }
            catch (e) {
                console.log(`failed: ${e.message}`);
                await sleep(ATTEMPT_DELAY_SECONDS * attempts * 1000);
                // we failed. try again until max retries
            }
        } while (attempts < MAX_RETRIES);

        notifyProgress(part_number, 1);

        abortController.abort();
    };

    for await (const chunk of file.stream()) {
        if (abortController.signal.aborted) {
            throw new Error("aborted");
        }

        if (part.length > CHUNK_SIZE) {
            const aligned = part.slice(0, CHUNK_SIZE);
            promises.push(doPartWithRetry(aligned, i));
            i++;
            part = part.slice(CHUNK_SIZE);

            if (promises.length > 3) await Promise.all(promises);
        }

        part = part.concat(Array.from(chunk));
    }

    // and then the remaining section
    promises.push(doPartWithRetry(part, i));

    await Promise.all(promises);

    if (abortController.signal.aborted)
        throw new Error("aborted");
};

const completeUpload = async (upload_id: string) => {
    const res = await fetch(`/file-multipart/ajax_done/${upload_id}`, {
        method: "POST",
    });

    if (!res.ok) throw new Error("failed");

    return await res.json();
};

const abortUpload = async (upload_id: string) => {
    await fetch(`/file/ajax_multipart/${upload_id}`, {
        method: "DELETE",
    });
};

const getFileMd5 = (file: File) => {
    console.time("md5")
    const gen = new spark();
    const reader = new FileReader();

    const chunks = Math.ceil(file.size / CHUNK_SIZE);
    let currentChunk = 0;

    const loadNext = () => {
        const start = currentChunk * CHUNK_SIZE;
        const end = start + CHUNK_SIZE >= file.size ? file.size : start + CHUNK_SIZE;

        reader.readAsArrayBuffer(file.slice(start, end));
    }

    const promise = new Promise<string>((resolve, reject) => {
        reader.addEventListener("load", (e) => {
            if (!e.target?.result || typeof e.target.result === "string")
                return reject(new Error("md5 result missing or wrong type?"));

            gen.append(e.target.result);
            currentChunk++;

            if (currentChunk < chunks) return loadNext();

            const res = gen.end();
            console.timeEnd("md5")
            resolve(res);
        });

        reader.addEventListener("error", (e) => reject(e));
    });

    loadNext();

    return promise;
}


export { abortUpload, beginMultipartUpload, completeUpload, uploadParts, getFileMd5, CHUNK_SIZE };


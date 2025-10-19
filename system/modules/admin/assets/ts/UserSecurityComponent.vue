<script setup lang="ts">
import { defineProps, ref, defineModel, onMounted } from 'vue';
import { startRegistration } from "~/@simplewebauthn/browser";

const props = defineProps<{
	user_id: string;
	locked: boolean;
	mfa_enabled: boolean
	pw_min_length: number;
	allow_adding_passkeys: boolean;
	allow_passkeys: boolean;
}>();

const locked = ref(props.locked);
const user_id = props.user_id;
const mfa_enabled = ref(props.mfa_enabled);
const pw_min_length = props.pw_min_length ? parseInt(props.pw_min_length) : 0;

const mfa_qr_code_url = ref<string>();
const mfa_secret = ref<string>();
const is_loading = ref(false);

const mfa_code = defineModel<string>("mfa_code");

const new_password = defineModel<string>("new_password");
const new_password_repeat = defineModel<string>("new_password_repeat");

const passkeys = ref();
const passkey_edit = ref();	// id of passkey being edited

onMounted(async () => {
	passkeys.value = await getPasskeys();
})

const displayToast = (msg: string) => {
	//@ts-ignore
	(new window.cmfive.toast(msg)).show();
}

const loadingBoundary = (msg: string, func: () => Promise<unknown> | unknown) => {
	return async () => {
		try {
			is_loading.value = true;
			await func();
			is_loading.value = false;
		}
		catch (e) {
			displayToast(msg);
			is_loading.value = false;
		}
	}
}

const updatePassword = loadingBoundary("Failed to update password", () => {
	if (!new_password.value || !new_password_repeat.value) {
		displayToast("Enter new password")
		return;
	}

	if (new_password.value != new_password_repeat.value) {
		displayToast("Passwords do not match");
		return;
	}

	if (pw_min_length && new_password.value.length < pw_min_length) {
		displayToast(`Password must be at least ${pw_min_length} characters`);
		return;
	}

	return fetch("/auth/ajax_update_password", {
		method: "POST",
		body: JSON.stringify({
			id: user_id,
			new_password: new_password.value,
			repeat_new_password: new_password_repeat.value,
		})
	})
		.then(x => {
			if (x.ok) return x.json();
			throw new Error();
		})
		.then(x => displayToast(x.message))
})

const unlockAccount = loadingBoundary("Failed to unlock account", () =>
	fetch("/admin-user/ajax_unlock_account", {
		method: "POST",
		body: JSON.stringify({
			id: user_id,
		})
	})
		.then(x => {
			if (x.ok) return x.json();
			throw new Error();
		})
		.then(() => {
			locked.value = false;
			displayToast("Account unlocked");
		})
);

const getMfaCode = loadingBoundary("Failed to fetch QR code", async () => {
	is_loading.value = true;

	const res = await fetch(`/auth/ajax_get_mfa_qr_code?id=${user_id}`);
	const json = await res.json();

	if (!res.ok) throw new Error();

	mfa_qr_code_url.value = json.data.qr_code;
	mfa_secret.value = json.data.mfa_secret;
	is_loading.value = false;
})

const disableMfa = loadingBoundary("Failed to disable MFA", async () => {
	if (!confirm("Are you sure you want to disable 2FA? This will greatly decrease the security of your account")) {
		return;
	}

	await fetch("/auth/ajax_disable_mfa", {
		method: "POST",
		body: JSON.stringify({
			id: user_id
		})
	})
		.then(x => {
			if (x.ok) return x.json();
			throw new Error();
		})
		.then(() => {
			displayToast("MFA disabled");
			mfa_enabled.value = false;
		})
})

const confirmMfaCode = loadingBoundary("Failed to enable MFA", async () => {
	await fetch("/auth/ajax_confirm_mfa_code", {
		method: "POST",
		body: JSON.stringify({
			id: user_id,
			mfa_code: mfa_code.value
		})
	})
		.then(x => {
			if (x.ok) return x.json();
			throw new Error();
		})
		.then(() => {
			mfa_qr_code_url.value = undefined;
			mfa_secret.value = undefined;
			mfa_enabled.value = true;
			displayToast("MFA enabled");
		})
})

const getPasskeys = async () => {
	return await fetch(`/auth-webauthn/ajax_get_keys/${user_id}`).then(x => x.json()).then(x => x.data.keys)
}

const registerPasskey = async () => {
	const options = await fetch("/auth-webauthn/ajax_init_register", { method: "POST" }).then(x => x.json());

	const attResp = await startRegistration({ optionsJSON: options });

	await fetch("/auth-webauthn/ajax_register", {
		method: "POST",
		body: JSON.stringify(attResp),
		headers: {
			'Content-Type': 'application/json',
		},
	});

	window.location.reload();
}

const removePasskey = async (id: string) => {
	await fetch(`/auth-webauthn/ajax_remove_key/${id}`);

	window.location.reload();
}

const savePasskeyName = async (ev: SubmitEvent) => {
	const data = new FormData(ev.target as HTMLFormElement);

	await fetch(`/auth-webauthn/ajax_nick/${passkey_edit.value}`, {
		method: "POST",
		body: JSON.stringify({
			name: data.get("name"),
		})
	});

	window.location.reload();
}
</script>

<template>
	<div class="row">
		<div class="col-6">
			<div v-if="locked">
				<div class="alert alert-warning d-flex justify-content-between align-items-center" data-alert="">
					This account is locked
					<button class="btn btn-danger" @click.prevent="unlockAccount" :disabled="is_loading">Unlock</button>
				</div>
			</div>

			<div class="panel mt-0">
				<div class="row g-0 section-header">
					<h4 class="col">Update Password</h4>
				</div>

				<div class="row">
					<form class="col">
						<div class="mb-3">
							<label for="password" class="form-label">New Password</label>
							<input name="password" type="password" required :minlength="pw_min_length"
								v-model="new_password" class="form-control">
						</div>

						<div class="mb-3">
							<label for="repeat_password" class="form-label">Repeat New Password</label>
							<input name="repeat_password" type="password" required :minlength="pw_min_length"
								v-model="new_password_repeat" class="form-control">
						</div>

						<button @click.prevent="updatePassword" :disabled="is_loading" class="btn btn-primary m-0"
							type="submit">
							Update Password
						</button>
					</form>
				</div>
			</div>
		</div>

		<div class="col-6">
			<div class="panel">
				<div class="row g-0 section-header">
					<h4 class="col">Google Authenticator</h4>
				</div>

				<button v-if="!mfa_enabled && !mfa_qr_code_url" @click.prevent="getMfaCode"
					class="btn btn-primary ms-0">
					Enable MFA
				</button>

				<button v-if="mfa_enabled" @click.prevent="disableMfa" class="btn btn-warning ms-0">
					Disable MFA
				</button>

				<div v-if="!!mfa_qr_code_url">
					<div>
						<img :src="mfa_qr_code_url" width="250" height="250" aria-describedby="mfa_code_image">
						<label id="mfa_code_image">
							Can't scan the code? Add it manually: {{ mfa_secret }}
						</label>
					</div>

					<form>
						<div class="mb-3">
							<label class="form-label" for="mfa_code">Code</label>
							<input v-model="mfa_code" name="mfa_code" required type="text" class="form-control" />
						</div>

						<button class="btn btn-primary m-0" @click.prevent="confirmMfaCode">Enable MFA</button>
					</form>
				</div>
			</div>

			<div class="panel" v-if="props.allow_passkeys">
				<h4 class="section-header">Passkey</h4>

				<button v-if="allow_adding_passkeys" @click.prevent="registerPasskey" class="btn btn-primary ms-0">
					Register device
				</button>

				<div class="mt-2" v-if="passkeys?.length">
					<h5>Registered passkeys</h5>

					<table class="table">
						<thead>
							<tr>
								<th scope="col">Name</th>
								<th scope="col">Created</th>
								<th scope="col"></th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(passkey, i) in passkeys" class="align-middle">
								<th scope="row">
									<form v-if="passkey_edit == passkey.id" @submit.prevent="savePasskeyName">
										<input class="form-control-sm" :value="passkey.name" aria-label="Passkey name"
											name="name" />
										<button type="submit" class="btn btn-sm btn-primary">Save</button>
									</form>
									<span v-else>
										{{ passkey.name ?? `Passkey ${i}` }}
										<button class="btn m-0 p-0" @click.prevent="passkey_edit = passkey.id"><i
												class="bi-pencil"></i></button>
									</span>
								</th>
								<th scope="row">{{ (new Date(passkey.dt_created * 1000)).toLocaleString() }}</th>
								<th scope="row">
									<button class="btn btn-danger ms-0"
										@click.prevent="() => removePasskey(passkey.id)">Remove</button>
								</th>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</template>
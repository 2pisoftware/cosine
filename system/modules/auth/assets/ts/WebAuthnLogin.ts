import { browserSupportsWebAuthn, startAuthentication } from '~/@simplewebauthn/browser';

const btn = document.getElementById("passkey_login") as HTMLButtonElement;

if (browserSupportsWebAuthn()) {
	btn.addEventListener("click", () => loginPasskey());
}
else {
	btn.setAttribute("disabled", "disabled");
	btn.style.display = "none";
}

const loginPasskey = async () => {
	const authOptions = await fetch("/auth-webauthn/ajax_init_login", {
		method: "POST",
	}).then(x => x.json())

	const asseResp = await startAuthentication({ optionsJSON: authOptions });

	const loginResp = await fetch("/auth-webauthn/ajax_login", {
		method: "POST",
		body: JSON.stringify(asseResp),
	});

	const { redirect_url } = (await loginResp.json()).data;

	if (!redirect_url) {
		btn.innerText = "Passkey failed. Try username and password";
		btn.classList.add("btn-danger");
		return;
	}

	window.location.href = redirect_url;
}
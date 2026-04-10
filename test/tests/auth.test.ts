import { expect } from "@playwright/test";
import { authenticator } from "otplib";
import { assertLoggedIn, cosineTest as test } from "@2pisoftware/cosine-tests";

test.describe("TOTP", () => {
	test.describe.configure({ mode: "serial" });

	test("register", async ({ page }) => {
		await assertLoggedIn(page);

		await page.goto("/auth/profile#user_security_app");

		const panel = page.locator(".panel", {
			has: page.getByText("Google Authenticator"),
		});

		await panel.waitFor();

		await panel.getByRole("button", { name: "Enable MFA" }).click();

		const label = panel.locator("#mfa_code_image");
		await label.waitFor();

		const secret = (await label.textContent())
			?.split(":")[1]
			.replaceAll(" ", "");

		expect(secret).toBeTruthy();
		if (!secret) return;

		const token = authenticator.generate(secret);

		await panel.locator('input[name="mfa_code"]').fill(token);

		await panel.getByRole("button", { name: "Enable MFA" }).click();

		await expect(panel.getByText("Disable MFA")).toBeVisible();
	});

	test("remove", async ({ page }) => {
		page.once("dialog", async (dialog) => {
			await dialog.accept();
		});

		await page.goto("/auth/profile#user_security_app");

		const panel = page.locator(".panel", {
			has: page.getByText("Google Authenticator"),
		});

		await panel.waitFor();

		// const promise = page.waitForResponse(/ajax_disable_mfa/);

		await panel.getByRole("button", { name: "Disable MFA" }).click();

		const resp = await page.waitForResponse(/ajax_disable_mfa/);

		expect(resp.ok()).toBeTruthy();
	});
});

import { expect } from "@playwright/test";

import {
	changeUserPassword,
	createRandomUser,
	createSession,
	getTableRow,
	cosineTest as test,
} from "@2pisoftware/cosine-tests";

test("Update password of a user", async ({
	adminPage: admin,
	cosine,
	browser,
}) => {
	const user = await createRandomUser(cosine);

	await changeUserPassword(admin, user.id, "test password");

	const newUser = await createSession(browser, user.login, "test password");

	await newUser.close();

	// we have made it to the home page as createSession navigates there
	expect(true);
});

test("Create user", async ({ adminPage: admin, browser }) => {
	await admin.goto("/admin/users");

	await admin
		.getByRole("button", { name: "Add New User", exact: true })
		.click();

	const form = admin.locator("#cmfive-modal form");

	await form.waitFor();

	// create the user

	const name = "create_users_test";
	await form.locator("#login").fill(name);
	await form.locator("#is_active").check();
	await form.locator("#password").fill(name);
	await form.locator("#password2").fill(name);

	await form.locator("#firstname").fill(name);
	await form.locator("#lastname").fill(name);
	await form.locator("#email").fill(`${name}@localhost`);

	await form.getByRole("button", { name: "Save" }).click();

	await admin.waitForLoadState();

	// and now we have to set the user role

	const row = getTableRow(admin, name);
	await row.getByRole("button", { name: "Permissions", exact: true }).click();

	await admin.waitForLoadState();

	await admin.locator("#check_user").check();

	await admin.getByRole("button", { name: "Save", exact: true }).click();

	await admin.waitForLoadState();

	// now check if we can login as them

	const newUser = await createSession(browser, name, name);

	await newUser.close();

	// we have made it to the home page as createSession navigates there
	expect(true);
});

test("Delete user", async ({ adminPage: admin, cosine }) => {
	const user = await createRandomUser(cosine);

	await admin.goto(`/admin-user/remove/${user.id}`);

	admin.once("dialog", (diag) => diag.accept());
	await admin.getByRole("button", { name: "Delete user", exact: true }).click();

	await expect(admin.getByText(`User ${user.login} deleted`)).toBeVisible();
});

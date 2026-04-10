import { expect } from "@playwright/test";

import {
	clickHeader,
	createTask,
	createTaskGroup,
	fillQuill,
	TaskGroupType,
	cosineTest as test,
	waitForModal,
} from "@2pisoftware/cosine-tests";

test.beforeEach(async ({ adminPage }) => {
	await createTaskGroup(
		adminPage,
		"test",
		TaskGroupType.Todo,
		"GUEST",
		"ALL",
		"ALL",
	);

	await createTask(adminPage, "test", "task");
});

test("Create using timer", async ({ adminPage }) => {
	// cosine ids are sequential, so this will be the task we just made
	await adminPage.goto("/task/edit/1");

	await adminPage.locator("#timelog_widget_start").click();

	const modal = adminPage.locator("#cmfive-modal");
	await modal.waitFor();

	// why is this capitalised?
	await fillQuill(adminPage, "Description", "--- TEST MARKER ---", modal);

	const promise = adminPage.waitForResponse((res) =>
		res.url().includes("timelog/ajaxStart/Task"),
	);

	await modal.getByRole("button", { name: "Save", exact: true }).click();

	await promise;

	const stopBtn = adminPage.locator("#timelog_widget_stop");

	await expect(stopBtn).toBeVisible();

	await stopBtn.click();

	await adminPage.goto("/timelog/index");

	await expect(
		adminPage.locator("tr", { hasText: "--- TEST MARKER ---" }),
	).toBeVisible();
});

test("Create using modal", async ({ adminPage }) => {
	await adminPage.goto("/task/edit/1");

	await clickHeader(adminPage, "Timelog", "Add Timelog");
	const modal = await waitForModal(adminPage);

	await modal.locator("#date_start").fill("2025-01-01");
	await modal.locator("#time_start").fill("12:00");

	await modal.locator("#select_end_method_hours").click();

	await modal.locator("#hours_worked").fill("1");

	await fillQuill(adminPage, "description", "--- TEST MARKER ---", modal);

	await modal.getByRole("button", { name: "Submit" }).click();

	await adminPage.waitForLoadState();

	await expect(
		adminPage.locator("tr", { hasText: "--- TEST MARKER ---" }).first(),
	).toBeVisible();
});

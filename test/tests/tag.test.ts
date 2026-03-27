import { expect } from "@playwright/test";

import {
	addTagToTarget,
	createTask,
	createTaskGroup,
	TaskGroupType,
	cosineTest as test,
} from "@2pisoftware/cosine-tests";

test("attach to objects", async ({ adminPage }) => {
	// create an object we can tag
	await createTaskGroup(
		adminPage,
		"test",
		TaskGroupType.Todo,
		"GUEST",
		"ALL",
		"ALL",
	);

	await createTask(adminPage, "test", "task");

	await adminPage.goto("/task/edit/1");

	await addTagToTarget(adminPage, "Task_1", "MARKER");
});

test("reuse", async ({ adminPage }) => {
	// create an object we can tag
	await createTaskGroup(
		adminPage,
		"test",
		TaskGroupType.Todo,
		"GUEST",
		"ALL",
		"ALL",
	);

	await createTask(adminPage, "test", "task1");
	await createTask(adminPage, "test", "task2");

	await adminPage.goto("/task/edit/1");

	await addTagToTarget(adminPage, "Task_1", "MARKER");

	await adminPage.goto("/task/edit/2");

	await addTagToTarget(adminPage, "Task_2", "MARKER");

	await adminPage.goto("/tag/admin");
	await expect(adminPage.locator("tr[data-id='0']")).toContainText("2");
});

import { expect } from "@playwright/test";

import {
	fillAutocomplete,
	getTableRow,
	TaskGroupType,
	TaskStatus,
	TaskType,
	cosineTest as test,
} from "@2pisoftware/cosine-tests";

// run these tests serially, as each new test depends on data created by previous tests
test.describe.configure({ mode: "serial" });

// TODO: run these tests as user instead of admin
// test.use({ defaultRoles: ["user", "task_user", "task_group"] });

test("Create task group", async ({ adminPage }) => {
	await adminPage.goto("/task-group/viewtaskgrouptypes");

	await adminPage
		.getByRole("link", { name: "New Task Group", exact: true })
		.click();

	await adminPage.getByLabel("Title").fill("test group");

	await adminPage
		.getByRole("combobox", { name: "Task Group Type" })
		.selectOption(TaskGroupType.SoftwareDevelopment);

	await adminPage
		.locator("#task_type")
		.selectOption(TaskType.ProgrammingTicket);

	await adminPage
		.getByRole("combobox", { name: "Who Can Assign" })
		.selectOption("OWNER");
	await adminPage
		.getByRole("combobox", { name: "Who Can View" })
		.selectOption("ALL");
	await adminPage
		.getByRole("combobox", { name: "Who Can Create" })
		.selectOption("ALL");
	await adminPage.locator("#priority").selectOption("Normal");

	await adminPage.getByRole("button", { name: "Save" }).click();

	await adminPage.waitForURL(/\/task-group\/viewmembergroup\/\d+/);

	await expect(
		adminPage.getByText("Task Group test group added"),
	).toBeVisible();
});

test("Create", async ({ adminPage }) => {
	await adminPage.goto("/task/edit");

	const promise = adminPage.waitForResponse((res) =>
		res.url().includes("taskAjaxSelectbyTaskGroup"),
	);
	await fillAutocomplete(adminPage, "task_group", "test group", "test group");
	await promise;
	await adminPage.keyboard.press("Escape");

	await adminPage.locator("#title").fill("test task");

	await adminPage
		.locator("#task_type")
		.selectOption(TaskType.ProgrammingTicket);

	await adminPage.getByRole("button", { name: "Save" }).click();

	await adminPage.waitForURL(/\/task\/edit\/\d+/);
});

test("Duplicate", async ({ adminPage }) => {
	await adminPage.goto("/task/tasklist");

	await adminPage.getByRole("button", { name: "Reset" }).first().click();

	await getTableRow(adminPage, "test task")
		.getByRole("link", { name: "test task" })
		.first()
		.click();

	await adminPage.getByRole("button", { name: "Duplicate Task" }).click();

	await adminPage.waitForURL(/\/task\/edit\/\d+/);

	await expect(adminPage.getByText("Task duplicated")).toBeVisible();
});

test("Edit", async ({ adminPage }) => {
	await adminPage.goto("/task/edit/1");

	await adminPage
		.getByRole("combobox", { name: "Status" })
		.selectOption(TaskStatus.Assigned);
	await adminPage
		.getByRole("combobox", { name: "Assigned To" })
		.selectOption("Unassigned");
	await expect(adminPage.getByText("Changed")).toBeVisible();
	await adminPage.getByRole("button", { name: "Save" }).click();
	await expect(
		adminPage.getByRole("combobox", { name: "Status Required" }),
	).toHaveValue(TaskStatus.Assigned);
});

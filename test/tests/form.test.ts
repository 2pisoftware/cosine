import { expect } from "@playwright/test";

import {
	createForm,
	createTask,
	createTaskGroup,
	FormFieldType,
	getTableRow,
	TaskGroupType,
	cosineTest as test,
	waitForModal,
} from "@2pisoftware/cosine-tests";

test.describe(() => {
	test.describe.configure({ mode: "serial" });

	test("create", async ({ adminPage }) => {
		await createForm(adminPage, "form 1", "random description");
	});

	test("edit", async ({ adminPage }) => {
		await adminPage.goto("/form");

		const row = getTableRow(adminPage, "form 1");
		await row.getByRole("button", { name: "Edit" }).click();

		const modal = await waitForModal(adminPage);

		await modal.locator("#title").fill("edited title");
		await modal.locator("#description").fill("new description");

		await modal.getByRole("button", { name: "Save" }).click();

		await expect(adminPage.getByText("Form updated")).toBeVisible();
	});

	test("delete", async ({ adminPage }) => {
		const row = getTableRow(adminPage, "edited title");

		adminPage.once("dialog", (diag) => diag.accept());

		await row.getByRole("button", { name: "Delete" }).click();
		await expect(adminPage.getByText("Form deleted")).toBeVisible();
	});
});

test.describe("with fields", () => {
	test.describe.configure({ mode: "serial" });

	test("create", async ({ adminPage }) => {
		const form = await createForm(adminPage, "form 2", "", [
			{
				key: "field1",
				name: "field1",
				type: FormFieldType.text,
			},
			{
				key: "field2",
				name: "field2",
				type: FormFieldType.boolean,
			},
		]);

		await adminPage.goto(`/form/show/${form}`);
	});

	test("renders in preview", async ({ adminPage }) => {
		await adminPage.getByRole("link", { name: "Preview" }).click();

		expect(adminPage.locator("#field1")).toBeVisible();
		expect(adminPage.locator("#field1")).toBeEditable();
		expect(adminPage.locator("#field1")).toHaveAttribute("type", "text");

		expect(adminPage.locator("#field2")).toBeVisible();
		expect(adminPage.locator("#field2")).toBeEditable();
		expect(adminPage.locator("#field2")).toHaveAttribute("type", "checkbox");
	});

	test("can map to object", async ({ adminPage }) => {
		await adminPage.getByRole("link", { name: "Mapping" }).click();

		await adminPage.locator("#task_single").click();

		await adminPage.getByRole("button", { name: "Save" }).click();

		await createTaskGroup(
			adminPage,
			"forms",
			TaskGroupType.Todo,
			"GUEST",
			"ALL",
			"ALL",
		);
		const taskId = await createTask(adminPage, "forms", "test task");

		await adminPage.goto(`/task/edit/${taskId}`);

		await adminPage.getByRole("link", { name: "form 2" }).click();

		await expect(adminPage.getByText("field1")).toBeVisible();
		await expect(adminPage.getByText("field2")).toBeVisible();
	});
});

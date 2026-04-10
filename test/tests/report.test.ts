import { expect } from "@playwright/test";

import {
	createTemplate,
	getTableRow,
	cosineTest as test,
} from "@2pisoftware/cosine-tests";

test.describe.configure({ mode: "serial" });

const REPORT_NAME = "TEST_REPORT";

test("Create", async ({ adminPage }) => {
	await adminPage.goto("/report/edit");

	await adminPage.locator("#title").fill(REPORT_NAME);
	await adminPage.locator("#module").selectOption({ label: "Admin" });
	await adminPage.locator(".savebutton").click();

	await expect(adminPage.getByText("Report created")).toBeVisible();
	await adminPage.locator("a[href='#code']", { hasText: "SQL" }).click();

	// Define report SQL
	const reportSQL =
		"[[test||text||Test]]@@headers|| select 'known' as 'pedigree' , 'established' as 'precedent' @@ @@info||select distinct classname from migration @@";
	await adminPage.locator("#code").getByRole("textbox").focus();
	await adminPage.keyboard.type(reportSQL);

	// Save report
	await adminPage.getByRole("button", { name: "Save Report" }).click();
});

test("Run", async ({ adminPage }) => {
	await adminPage.goto("/report/index");

	await expect(
		adminPage.getByRole("link", { name: REPORT_NAME }).first(),
	).toBeVisible();

	await adminPage.getByRole("link", { name: REPORT_NAME }).first().click();

	await adminPage.getByRole("button", { name: "Execute Report" }).click();

	await adminPage.locator("#test").fill("Hello");
	await adminPage.getByRole("button", { name: "Display Report" }).click();

	await expect(adminPage.getByText("known")).toBeVisible();
	await expect(adminPage.getByText("precedent")).toBeVisible();
});

test("Duplicate", async ({ adminPage }) => {
	await adminPage.goto("/report/index");

	await expect(
		adminPage.getByRole("link", { name: REPORT_NAME }).first(),
	).toBeVisible();

	await getTableRow(adminPage, REPORT_NAME).getByText("Duplicate").click();

	await expect(
		adminPage.getByText("Successfully duplicated Report"),
	).toBeVisible();

	await adminPage.goto("/report/index");

	await expect(
		adminPage.locator("#body-content table tbody a", {
			hasText: `${REPORT_NAME} - Copy`,
		}),
	).toHaveCount(1);
});

test("Delete", async ({ adminPage }) => {
	await adminPage.goto("/report/index");

	await expect(
		adminPage
			.getByRole("link", { name: `${REPORT_NAME} - Copy`, exact: true })
			.first(),
	).toBeVisible();

	adminPage.once("dialog", (diag) => void diag.accept());

	await getTableRow(adminPage, `${REPORT_NAME} - Copy`)
		.getByText("Delete")
		.click();

	await expect(adminPage.getByText("Report deleted")).toBeVisible();
});

test("Attach templates", async ({ adminPage }) => {
	const template_id = await createTemplate(
		adminPage,
		"test template",
		"Report",
		"report_template",
		[
			"<table width='100%' align='center' class='form-table' cellpadding='1'>",
			"    <tr>",
			"        <td colspan='2' style='border:none;'>",
			"            <img width='400' src='' style='width: 400px;' />",
			"        </td>",
			"        <td colspan='2' style='border:none; text-align:right;'>",
			"            Test Company<br/>",
			"            123 Test St, Test Town, NSW 1234<br/>",
			"            test@example.com<br/>",
			"            ACN: 123456789<br/>",
			"            ABN: 12345678901<br/>",
			"        </td>",
			"    </tr>",
			"</table>",
		],
	);

	await adminPage.goto("/report/edit/1");

	await adminPage
		.locator("a[href='#templates']", { hasText: "Templates" })
		.click();
	await adminPage
		.locator("#templates button", { hasText: "Add Template" })
		.click();
	await adminPage.locator("#template_id").selectOption({ value: template_id });
	await adminPage.locator("#type").selectOption({ label: "HTML" });
	await adminPage.getByRole("button", { name: "Save" }).click();

	await adminPage.goto("/report/runreport/1");

	await adminPage.locator("#test").fill("Hello");

	await adminPage
		.locator("#body-content #template")
		.selectOption("test template");
	await adminPage.getByRole("button", { name: "Display Report" }).click();

	await expect(adminPage.getByText("Test Company")).toBeVisible();
	await expect(adminPage.getByText("ABN: 12345678901")).toBeVisible();
});

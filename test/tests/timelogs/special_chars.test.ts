import { clickHeader, createTask, createTaskGroup, fillQuill, TaskGroupType, cosineTest as test, waitForModal } from "@2pisoftware/cosine-tests"
import { expect } from "@playwright/test";

test.beforeEach(async ({ adminPage }) => {
    await createTaskGroup(
        adminPage,
        "special_chars_group",
        TaskGroupType.Todo,
        "GUEST",
        "ALL",
        "ALL",
    );
});

[
    { taskName: "\"" },
    { taskName: "\'" },
    { taskName: "\"hello this is a longer string\"" },
    { taskName: "/,.:[]=-\\|~!@#$%^&*()" },
].forEach(async ({ taskName }) => {
    test.describe(() => {
        test.beforeEach(async ({ adminPage }) => {
            const id = await createTask(adminPage, "special_chars_group", taskName);

            await adminPage.goto(`/task/edit/${id}`);
        })

        test(`Can create timelog for task with name ${taskName}`, async ({ adminPage }) => {
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
        })
    })
})
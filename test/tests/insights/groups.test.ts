import { createRandomUser, createSession, getTableRow, cosineTest as test, waitForModal } from "@2pisoftware/cosine-tests";
import { expect } from "@playwright/test";

test("Groups can access insights", async ({ adminPage, cosine, browser }) => {
    const user = await createRandomUser(cosine, ["user", "insights_user"]);
    const page = await createSession(browser, user.login, user.password);

    // create a group
    await adminPage.goto("/admin/groupadd");
    await adminPage.getByLabel("Group title").fill("test_group");
    await adminPage.getByRole("button", { name: "Save" }).click();

    // give it the insights_user permission
    await getTableRow(adminPage, "test_group").getByRole("button", { name: "Edit" }).click();
    await adminPage.getByRole("button", { name: "Edit Permissions" }).click();
    await adminPage.locator("#check_insights_user").check();
    await adminPage.getByRole("button", { name: "Save" }).click();

    // assign our user to the group
    await adminPage.getByRole("button", { name: "New Member" }).click();
    const modal = await waitForModal(adminPage);
    await modal.locator("#member_id").selectOption({ value: `${user.id}` });
    await modal.getByRole("button", { name: "Save" }).click();

    // give group permission to the insight
    await adminPage.goto("/insights-members/editMembers?insight_class=MyTaskTimeInsight");
    await adminPage.getByLabel("Add Member").selectOption({ label: "Test_group" });
    await adminPage.getByLabel("With Role").selectOption({ label: "MEMBER" });
    await adminPage.getByRole("button", { name: "Save" }).click();

    // now check if user can view the insight
    await page.goto("/insights");
    await expect(getTableRow(page, "My Task Time")).toBeVisible();

    await page.goto("/insights/viewInsight/MyTaskTimeInsight");
    await expect(page.getByText("You do not have permission to view this insight")).not.toBeVisible();

    await page.close();
})
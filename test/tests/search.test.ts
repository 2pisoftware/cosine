import { cosineTest as test, waitForModal } from "@2pisoftware/cosine-tests";
import { expect } from "@playwright/test";

test("Should autofocus input", async ({ adminPage: page }) => {
    await page.locator("#navbar a.nav-link[data-modal-target=\"/search?isbox=1\"]").click();

    const modal = await waitForModal(page);

    const queryInput = modal.locator("#q");

    expect(queryInput).toBeFocused();
})
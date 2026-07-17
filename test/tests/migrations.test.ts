import { expect } from "@playwright/test";

import { cosineTest as test } from "@2pisoftware/cosine-tests";

test("Migrations", async ({ cosine }) => {
    await test.step("Revert all", async () => {
        const res = await cosine.exec("/var/www/html/cmfive.php revert migrations");
        expect(res.stderr.includes("PHP Fatal error")).toBeFalsy();
    })

    await test.step("Run all", async () => {
        const res = await cosine.exec("/var/www/html/cmfive.php install migrations");
        expect(res.stderr.includes("PHP Fatal error")).toBeFalsy();
    })
})
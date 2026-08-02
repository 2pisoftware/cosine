import { expect } from "@playwright/test";
import { executeSqlQuery, cosineTest as test } from "@2pisoftware/cosine-tests";
import { type ContainerRuntimeClient } from "testcontainers";

test("Migrations", async ({ cosine, mysql, runtime }) => {
    await test.step("Revert all", async () => {
        const before = new Set(await getTables(runtime, mysql));

        const revertRes = await cosine.exec("/var/www/html/cmfive.php revert migrations");
        expect(revertRes.stderr.includes("PHP Fatal error"), revertRes.output).toBeFalsy();

        const after = new Set(await getTables(runtime, mysql))

        const diff = before.intersection(after);

        // These migrations are the 'base' migrations that cosine won't let you revert normally
        expect([...diff]).toMatchObject([
            "audit",
            "comment",
            "lookup",
            "migration",
            "migration_seed",
            "phinxlog",
            "printer",
            "tag_assign",
            "template",
        ])
    })

    await test.step("Run all", async () => {
        const res = await cosine.exec("/var/www/html/cmfive.php install migrations");
        expect(res.stderr.includes("PHP Fatal error"), res.output).toBeFalsy();
    })
})

const getTables = async (runtime: ContainerRuntimeClient, database: string) => {
    const container = runtime.container.getById(
        process.env.SQL_CONTAINER_ID as string,
    );

    const dbRes = await runtime.container.exec(container, ["mysql",
        "-h",
        "127.0.0.1",
        "-u",
        "root",
        "-proot",
        "-D",
        database,
        "-e",
        "show tables"
    ]);

    return dbRes.stdout.split("\n")
        .filter(x => !x.includes("[Warning]") && !x.includes("Tables_in") && !!x);
}
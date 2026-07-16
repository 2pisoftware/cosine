import { defineConfig, devices } from '@playwright/test';
import path from "node:path";

if (!process.env.CI) {
    process.env.LOCAL_SYSTEM_DIR = path.join(import.meta.dirname, "..", "system");
    process.env.LOCAL_MODULE_DIR = path.join(import.meta.dirname, "..", "..", "modules");
}

export default defineConfig({
	tsconfig: "./tsconfig.json",

	testDir: "./tests",
	fullyParallel: true,

	timeout: 120_000,

	forbidOnly: !!process.env.CI,
	workers: process.env.WORKERS
		? Number.parseInt(process.env.WORKERS, 10)
		: undefined,
	retries: process.env.RETRIES
		? Number.parseInt(process.env.RETRIES, 10)
		: undefined,

	reporter: [["junit", { outputFile: "./test-results/junit.xml" }]],
	use: {
		trace: "on",
		screenshot: "on",
	},

  globalSetup: new URL(import.meta.resolve("@2pisoftware/cosine-tests/dist/global.setup.js")).pathname,

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },

    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },

    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
  ],
});

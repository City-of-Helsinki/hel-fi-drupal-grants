import { defineConfig } from '@playwright/test';


/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  // Look for test files in the "tests" directory, relative to this configuration file.
  testDir: 'tests',

  // Timeout for each test in milliseconds
  timeout: 180 * 1000,

  // Run all tests in parallel.
  fullyParallel: false,

  // Fail the build on CI if you accidentally left test.only in the source code.
  forbidOnly: !!process.env.CI,

  // Retry on CI only.
  retries: process.env.CI ? 2 : 0,

  // The maximum number of concurrent worker processes to use for parallelizing tests
  workers: 1,

  // Reporter to use
  reporter: process.env.CI ? [['junit', { outputFile: 'test-results/e2e-junit-results.xml' }], ['html']] : 'html',

  use: {
    // Default timeout for each Playwright action in milliseconds
    actionTimeout: 15 * 1000,

    // Base URL to use in actions like `await page.goto('/')`.
    baseURL: process.env.TEST_BASEURL ?? "https://hel-fi-drupal-grant-applications.docker.so",

    // Capture screenshot after each test failure
    screenshot: { fullPage: true, mode: "only-on-failure" },

    // Collect trace when retrying the failed test.
    trace: 'on-first-retry',
  },

  projects: [
    {
      name: 'Setup',
      testMatch: '**/global.setup.ts',
    },
    {
      name: 'Profiles',
      testMatch: '**/grant-profiles.setup.ts',
      dependencies: ['Setup'],
    },
    {
      name: 'Authentication',
      testMatch: '**/auth.setup.ts',
      dependencies: ['Setup'],
    },
    {
      name: "Forms",
      testMatch: [/forms/],
      dependencies: ['Profiles', 'Authentication'],
      use: { storageState: ".auth/user.json" },
    },
    {
      name: "My services",
      testMatch: [/my_services/],
      dependencies: ['Profiles', 'Authentication'],
      use: { storageState: ".auth/user.json" },
    },
    {
      name: "Public",
      testMatch: [/public/],
      dependencies: ['Setup'],
    }
  ],
});

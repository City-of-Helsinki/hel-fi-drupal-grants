import { defineConfig, devices } from '@playwright/test';


/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: './tests',
  timeout: 180 * 1000,
  /* Run tests in files in parallel */
  fullyParallel: false,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  workers: 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: process.env.CI ? [
    ['junit', { outputFile: 'test-results/e2e-junit-results.xml' }],
    ['html']
  ]
    : 'html',
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    actionTimeout: 30 * 1000,
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: process.env.TEST_BASEURL || "https://hel-fi-drupal-grant-applications.docker.so",
    ignoreHTTPSErrors: true,
    screenshot: {
      fullPage: true,
      mode: "only-on-failure"
    },
    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
  },


  projects: [
    {
      name: 'setup',
      testMatch: '**/global.setup.ts',
    },
    {
      name: 'clean-env',
      testMatch: '**/clean_env.setup.ts',
      dependencies: ['setup'],
    },
    {
      name: 'auth-setup',
      testMatch: '**/auth.setup.ts',
      dependencies: ['setup'],
    },
    {
      name: 'logged-in',
      testMatch: [/forms/, /my_services/],
      dependencies: ['clean-env', 'auth-setup'],
      use: {
        ...devices['Desktop Chrome'],
        storageState: ".auth/user.json"
      },
    },
    {
      name: 'logged-out',
      testMatch: [/public/],
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'] },
    }
  ],
});

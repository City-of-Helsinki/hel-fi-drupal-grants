import { defineConfig, devices } from '@playwright/test';

require('dotenv').config({ path: '../.env' });
require('dotenv').config({ path: '.test_env' });

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: './tests',
  timeout: 90 * 1000,
  /* Run tests in files in parallel */
  fullyParallel: false,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  workers: 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: 'html',
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: "https://" + process.env.DRUPAL_HOSTNAME,
    ignoreHTTPSErrors: true,
    screenshot: "only-on-failure",
    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
  },


  projects: [
    {
      name: 'clean-env',
      testMatch: '**/clean_env.setup.ts',
    },
    {
      name: 'auth-setup',
      testMatch: '**/auth.setup.ts',
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
      use: {
        ...devices['Desktop Chrome'],
      },
    }
  ],
});

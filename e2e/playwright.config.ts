import {defineConfig} from '@playwright/test';
import 'dotenv/config';

/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  globalTeardown: require.resolve('./tests/global.teardown.ts'),
  globalSetup: require.resolve('./tests/init.setup.ts'),
  testDir: './tests',
  timeout: 300 * 1000,
  /* Run tests in files in parallel */
  fullyParallel: false,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  workers: 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: process.env.CI ? [
      ['junit', {outputFile: 'test-results/e2e-junit-results.xml'}],
      ['html']
    ]
    : 'html',
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Wait for maximum of 120 seconds. Drop the timeout to 60s when */
    /* development server cpu and memory issues have been fixed. */
    actionTimeout: 120 * 1000,
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: process.env.TEST_BASEURL ?? "https://hel-fi-drupal-grant-applications.docker.so",
    ignoreHTTPSErrors: true,
    screenshot: {
      fullPage: true,
      mode: "only-on-failure"
    },
    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
    launchOptions: {
      slowMo: process.env.SLOWMO ? 1_000 : 0,
    },
  },
  // For expect calls
  expect: {
    timeout: 10000,   // <---------
  },
  projects: [
    /* Setup and auth setup tests. */
    {
      name: 'setup',
      testMatch: '**/global.setup.ts',
    },
    {
      name: 'auth-setup',
      testMatch: '**/auth.setup.ts',
      dependencies: ['setup'],
    },
    /* Profile tests. */
    {
      name: 'profiles',
      testMatch: [
        '/profiles/private_person.ts',
        '/profiles/unregistered_community.ts',
        '/profiles/registered_community.ts',
      ],
      dependencies: ['auth-setup']
    },
    {
      name: 'profile-private_person',
      testMatch: '/profiles/private_person.ts',
      dependencies: ['auth-setup']
    },
    {
      name: 'profile-unregistered_community',
      testMatch: '/profiles/unregistered_community.ts',
      dependencies: ['auth-setup']
    },
    {
      name: 'profile-registered_community',
      testMatch: '/profiles/registered_community.ts ',
      dependencies: ['auth-setup']
    },
    /* Form tests by user profile (role). */
    {
      name: 'forms-private',
      testMatch: '/forms/private_person_*',
      dependencies: ['profile-private_person']
    },
    {
      name: 'forms-unregistered',
      testMatch: '/forms/unregistered_community_*',
      dependencies: ['profile-unregistered_community']
    },
    {
      name: 'forms-registered',
      testMatch: '/forms/registered_community_*',
      dependencies: ['profile-registered_community']
    },
    /* Run all form tests. */
    {
      name: 'forms-all',
      testMatch: '/forms/*',
      dependencies: ['profile-private_person', 'profile-unregistered_community', 'profile-registered_community']
    },
    /* Run all smoke tests. */
    {
      name: 'smoke',
      testMatch: '/public/*',
      dependencies: ['setup']
    },
    /* Form 29 tests. */
    {
      name: 'forms-29',
      testMatch: '/forms/registered_community_29.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 48 tests. */
    {
      name: 'forms-48',
      testMatch: /forms\/.*_48\.ts$/,
      dependencies: ['profile-private_person', 'profile-unregistered_community', 'profile-registered_community']
    },
    {
      name: 'forms-48-private',
      testMatch: '/forms/private_person_48.ts',
      dependencies: ['profile-private_person']
    },
    {
      name: 'forms-48-unregistered',
      testMatch: '/forms/unregistered_community_48.ts',
      dependencies: ['profile-unregistered_community']
    },
    {
      name: 'forms-48-registered',
      testMatch: '/forms/registered_community_48.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 49 tests. */
    {
      name: 'forms-49',
      testMatch: '/forms/registered_community_49.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 50 tests. */
    {
      name: 'forms-50',
      testMatch: '/forms/registered_community_50.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 51 tests. */
    {
      name: 'forms-51',
      testMatch: '/forms/registered_community_51.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 52 tests. */
    {
      name: 'forms-52',
      testMatch: '/forms/registered_community_52.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 53 tests. */
    {
      name: 'forms-53',
      testMatch: '/forms/registered_community_53.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 54 tests. */
    {
      name: 'forms-54',
      testMatch: '/forms/registered_community_54.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 56 tests. */
    {
      name: 'forms-56',
      testMatch: '/forms/registered_community_56.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 57 tests. */
    {
      name: 'forms-57',
      testMatch: '/forms/registered_community_57.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 58 tests. */
    {
      name: 'forms-58',
      testMatch: '/forms/registered_community_58.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 60 tests. */
    {
      name: 'forms-60',
      testMatch: '/forms/registered_community_60.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 61 tests. */
    {
      name: 'forms-61',
      testMatch: '/forms/registered_community_61.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 62 tests. */
    {
      name: 'forms-62',
      testMatch: /forms\/.*_62\.ts$/,
      dependencies: ['profile-unregistered_community', 'profile-registered_community']
    },
    {
      name: 'forms-62-unregistered',
      testMatch: '/forms/unregistered_community_62.ts',
      dependencies: ['profile-unregistered_community']
    },
    {
      name: 'forms-62-registered',
      testMatch: '/forms/registered_community_62.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 63 tests. */
    {
      name: 'forms-63',
      testMatch: '/forms/registered_community_63.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 64 tests. */
    {
      name: 'forms-64',
      testMatch: /forms\/.*_64\.ts$/,
      dependencies: ['profile-private_person', 'profile-unregistered_community', 'profile-registered_community']
    },
    {
      name: 'forms-64-private',
      testMatch: '/forms/private_person_64.ts',
      dependencies: ['profile-private_person']
    },
    {
      name: 'forms-64-unregistered',
      testMatch: '/forms/unregistered_community_64.ts',
      dependencies: ['profile-unregistered_community']
    },
    {
      name: 'forms-64-registered',
      testMatch: '/forms/registered_community_64.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 65 tests. */
    {
      name: 'forms-65',
      testMatch: /forms\/.*_65\.ts$/,
      dependencies: ['profile-unregistered_community', 'profile-registered_community']
    },
    {
      name: 'forms-65-unregistered',
      testMatch: '/forms/unregistered_community_65.ts',
      dependencies: ['profile-unregistered_community']
    },
    {
      name: 'forms-65-registered',
      testMatch: '/forms/registered_community_65.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 66 tests. */
    {
      name: 'forms-66',
      testMatch: /forms\/.*_66\.ts$/,
      dependencies: ['profile-unregistered_community', 'profile-registered_community']
    },
    {
      name: 'forms-66-unregistered',
      testMatch: '/forms/unregistered_community_66.ts',
      dependencies: ['profile-unregistered_community']
    },
    {
      name: 'forms-66-registered',
      testMatch: '/forms/registered_community_66.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 67 tests. */
    {
      name: 'forms-67',
      testMatch: '/forms/registered_community_67.ts',
      dependencies: ['profile-registered_community'],
    },
    /* Form 68 tests. */
    {
      name: 'forms-68',
      testMatch: '/forms/registered_community_68.ts',
      dependencies: ['profile-registered_community'],
    },
    /* Form 69 tests. */
    {
      name: 'forms-69',
      testMatch: /forms\/.*_69\.ts$/,
      dependencies: ['profile-unregistered_community', 'profile-registered_community']
    },
    {
      name: 'forms-69-unregistered',
      testMatch: '/forms/unregistered_community_69.ts',
      dependencies: ['profile-unregistered_community']
    },
    {
      name: 'forms-69-registered',
      testMatch: '/forms/registered_community_69.ts',
      dependencies: ['profile-registered_community']
    },
    /* Form 70 tests. */
    {
      name: 'forms-70',
      testMatch: '/forms/registered_community_70.ts',
      dependencies: ['profile-registered_community']
    },
  ],
});

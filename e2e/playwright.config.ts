import {defineConfig} from '@playwright/test';
import 'dotenv/config';


/**
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  globalTeardown: require.resolve('./tests/global.teardown.ts'),
  globalSetup: require.resolve('./tests/init.setup.ts'),
  testDir: './tests',
  timeout: 180 * 1000,
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
    actionTimeout: 60 * 1000,
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: process.env.TEST_BASEURL ?? "https://hel-fi-drupal-grant-applications.docker.so",
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
      name: 'auth-setup',
      testMatch: '**/auth.setup.ts',
      dependencies: ['setup'],
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
    // {
    //     name: 'profiles',
    //     testMatch: '/profiles/*'
    // },
    {
      name: 'forms-private',
      testMatch: '/forms/private_person_*',
      dependencies: ['profile-private_person']
    },
    {
      name: 'forms-registered',
      testMatch: '/forms/registered_community_*',
      dependencies: ['profile-registered_community']
    },
    {
      name: 'forms-unregistered',
      testMatch: '/forms/unregistered_community_*',
      dependencies: ['profile-unregistered_community']
    },
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
      name: 'forms-48-registered',
      testMatch: '/forms/registered_community_48.ts',
      dependencies: ['profile-registered_community']
    },
    {
      name: 'forms-29',
      testMatch: /forms\/.*_29\.ts$/,
      dependencies: ['profile-registered_community']
    },
    {
      name: 'forms-53-reg',
      testMatch: '/forms/registered_community_53.ts',
      dependencies: ['profile-registered_community']
    },
    {
      name: 'forms-54-reg',
      testMatch: '/forms/registered_community_54.ts',
      dependencies: ['profile-registered_community']
    },
    {
      name: 'forms-51',
      testMatch: /forms\/.*_51\.ts$/,
      dependencies: ['profile-registered_community']
    },
    {
      name: 'forms-56',
      testMatch: /forms\/.*_56\.ts$/,
      dependencies: ['profile-private_person', 'profile-unregistered_community', 'profile-registered_community']
    },
    {
      name: 'forms-60',
      testMatch: /forms\/.*_60\.ts$/,
      dependencies: ['profile-registered_community']
    },
    {
      name: 'forms-62',
      testMatch: /forms\/.*_62\.ts$/,
      dependencies: ['profile-unregistered_community', 'profile-registered_community']
    },
    {
      name: 'forms-63',
      testMatch: /forms\/.*_63\.ts$/,
      dependencies: ['profile-registered_community']
    },
    {
      name: 'forms-64',
      testMatch: /forms\/.*_64\.ts$/,
      dependencies: ['profile-private_person', 'profile-unregistered_community', 'profile-registered_community']
    },
    {
      name: 'forms-65',
      testMatch: /forms\/.*_65\.ts$/,
      dependencies: ['profile-unregistered_community', 'profile-registered_community']
    },
    {
      name: 'forms-65-reg',
      testMatch: '/forms/registered_community_65.ts',
      dependencies: ['profile-registered_community']
    },
    {
      name: 'forms-65-unreg',
      testMatch: '/forms/unregistered_community_65.ts',
      dependencies: ['profile-unregistered_community']
    },
    {
      name: 'forms-66',
      testMatch: /forms\/.*_66\.ts$/,
      dependencies: ['profile-registered_community', 'profile-unregistered_community']
    },
    {
      name: 'forms-68',
      testMatch: /forms\/.*_68\.ts$/,
      dependencies: ['profile-registered_community']
    },
    {
      name: 'forms-69',
      testMatch: /forms\/.*_69\.ts$/,
      dependencies: ['profile-registered_community', 'profile-unregistered_community']
    },
    // {
    //   name: 'logged-in',
    //   testMatch: [/forms/, /my_services/],
    //   dependencies: ['clean-env', 'auth-setup'],
    //   use: {
    //     storageState: ".auth/user.json"
    //   },
    // },
    // {
    //   name: 'logged-out',
    //   testMatch: [/public/],
    //   dependencies: ['setup'],
    // }
  ],
});

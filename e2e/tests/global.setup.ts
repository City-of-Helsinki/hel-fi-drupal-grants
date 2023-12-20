import {test as setup, expect} from '@playwright/test';
import {
  ATV_BASE_URL,
  ATV_API_KEY,
  getAppEnvForATV,
} from "../utils/document_helpers";

const APP_ENV = getAppEnvForATV();

/**
 * Setup environment. So far only for env variables, can be extended in the future.
 */
setup('Setup environment', async () => {
  expect(ATV_API_KEY).toBeTruthy()
  expect(ATV_BASE_URL).toBeTruthy()
  expect(APP_ENV).toBeTruthy()
  expect(APP_ENV.toUpperCase()).not.toContain("PROD");
})


setup('Maintenance mode should be off', async ({page}) => {
  console.log('Check maintenance mode')
  await page.goto('/');
  await expect(page.locator(".maintenance-page")).toBeHidden();
});

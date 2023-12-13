import { Page, expect } from '@playwright/test';
import { AUTH_FILE_PATH } from './constants';
import { TEST_SSN } from './test_data';
import { selectRole } from './role';

export const login = async (page: Page, SSN: string = TEST_SSN) => {
  // Locators
  const loginButton = page.locator('#edit-openid-connect-client-tunnistamo-login');
  const selectIdentificationMethodButton = page.locator('#fakevetuma2');
  const ssnInput = page.locator('#hetu_input');
  const tunnistauduButton = page.locator('#tunnistaudu');
  const continueToServiceButton = page.locator('#continue-button');

  // Login steps
  await page.goto('/fi/user/login');
  await loginButton.click();
  await selectIdentificationMethodButton.click();
  await ssnInput.fill(SSN);
  await page.locator('.box').click(); // Workaround to enable "tunnistauduButton"
  await tunnistauduButton.click();
  await continueToServiceButton.click();
  await expect(page.getByTitle('Helsingin kaupunki')).toBeVisible({ timeout: 60 * 1000 });
};

export const loginWithCompanyRole = async (page: Page, SSN?: string) => {
  await login(page, SSN);
  await selectRole(page, 'REGISTERED_COMMUNITY');
};

export const loginAsPrivatePerson = async (page: Page, SSN?: string) => {
  await login(page, SSN);
  await selectRole(page, 'PRIVATE_PERSON');
};

export const loginAndSaveStorageState = async (page: Page) => {
  await login(page);
  await page.context().storageState({ path: AUTH_FILE_PATH });
};

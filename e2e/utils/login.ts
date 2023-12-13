import { Page } from '@playwright/test';
import { AUTH_FILE_PATH } from './constants';
import { TEST_SSN } from './test_data';
import { selectRole } from './role';

export const login = async (page: Page, SSN: string = TEST_SSN) => {
  await page.goto('/fi/user/login');
  await page.locator('#edit-openid-connect-client-tunnistamo-login').click();
  await page.locator('#fakevetuma2').click();
  await page.locator('#hetu_input').fill(SSN);
  await page.locator('.box').click();
  await page.locator('#tunnistaudu').click();
  await page.locator('#continue-button').click();
  await page.waitForSelector('text="Helsingin kaupunki"');
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

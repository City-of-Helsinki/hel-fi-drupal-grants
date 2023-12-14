import { Page, expect } from '@playwright/test';
import { loginAndSaveStorageState } from './login';
import { Role } from './types';

export const selectRole = async (page: Page, role: Role) => {
  await page.goto('/fi/asiointirooli-valtuutus');

  const pageUnavailable = await page.getByText('Sinulla ei ole käyttöoikeutta tälle sivulle').isVisible();

  if (pageUnavailable) {
    page.context().clearCookies();
    await loginAndSaveStorageState(page);
  }

  const loggedInAsCompanyUser = await page.locator('.asiointirooli-block-registered_community').isVisible();
  const loggedAsPrivatePerson = await page.locator('.asiointirooli-block-private_person').isVisible();
  const loggedInAsUnregisteredCommunity = await page.locator('.asiointirooli-block-unregistered_community').isVisible();

  if (role === 'REGISTERED_COMMUNITY' && !loggedInAsCompanyUser) {
    await selectRegisteredCommunityRole(page);
  }

  if (role === 'UNREGISTERED_COMMUNITY' && !loggedInAsUnregisteredCommunity) {
    await selectUnregisteredCommunityRole(page);
  }

  if (role === 'PRIVATE_PERSON' && !loggedAsPrivatePerson) {
    await selectPrivatePersonRole(page);
  }
};

const selectRegisteredCommunityRole = async (page: Page) => {
  // Locators
  const registeredCommunityButton = page.locator('[name="registered_community"]');
  const radioButtons = page.getByRole('radio').first();
  const confirmButton = page.locator('[data-test="perform-confirm"]');
  const cityOfHelsinkiTitle = page.getByTitle('Helsingin kaupunki');
  const companyList = page.locator('tbody');

  // Steps
  await registeredCommunityButton.click();
  await expect(companyList).toBeVisible({ timeout: 60 * 1000 });
  await radioButtons.first().click({ force: true }); // Bypass intercepting label tag by force
  await confirmButton.click({ force: true, noWaitAfter: true });
  await expect(cityOfHelsinkiTitle).toBeVisible({ timeout: 60 * 1000 });
};

export const selectUnregisteredCommunityRole = async (page: Page) => {
  await page.locator('#edit-unregistered-community-selection').selectOption({ index: 2 });
  await page.locator('[name="unregistered_community"]').click();
};

export const selectPrivatePersonRole = async (page: Page) => {
  await page.locator('[name="private_person"]').click();
};

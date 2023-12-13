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

export const selectRegisteredCommunityRole = async (page: Page) => {
  const registeredCommunityButton = page.locator('[name="registered_community"]');
  await expect(registeredCommunityButton).toBeVisible();
  await registeredCommunityButton.click();
  await expect(page.locator('input[type="radio"]').first()).toBeVisible({ timeout: 60 * 1000 });
  const firstCompanyRow = page.locator('input[type="radio"]').first();
  await firstCompanyRow.check({ force: true });
  await page.locator('[data-test="perform-confirm"]').click();
};

export const selectUnregisteredCommunityRole = async (page: Page) => {
  await page.locator('#edit-unregistered-community-selection').selectOption({ index: 2 });
  await page.locator('[name="unregistered_community"]').click();
};

export const selectPrivatePersonRole = async (page: Page) => {
  await page.locator('[name="private_person"]').click();
};

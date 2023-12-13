import { Page, expect } from '@playwright/test';

export const startNewApplication = async (page: Page, applicationName: string) => {
  await page.goto('fi/etsi-avustusta');

  const searchInput = page.locator('#edit-search');
  const searchButton = page.locator('#edit-submit-application-search-search-api');

  await searchInput.fill(applicationName);
  await searchButton.click();

  const linkToApplication = page.getByRole('link', { name: applicationName });
  await expect(linkToApplication).toBeVisible();
  await linkToApplication.click();

  await page.locator('a[href*="uusi-hakemus"]').first().click();
};

export const checkErrorNofification = async (page: Page) => {
  const errorNotificationVisible = await page.locator('form .hds-notification--error').isVisible();
  let errorText = '';

  if (errorNotificationVisible) {
    errorText = (await page.locator('form .hds-notification--error').textContent()) ?? 'Application preview page contains errors';
  }

  expect(errorNotificationVisible, errorText).toBeFalsy();
};

export const acceptCookies = async (page: Page) => {
  const acceptCookiesButton = page.getByRole('button', { name: 'Hyväksy vain välttämättömät evästeet' });
  await acceptCookiesButton.click();
};

export const clickContinueButton = async (page: Page) => {
  const continueButton = page.getByRole('button', { name: 'Seuraava' });
  await continueButton.click();
};

export const clickGoToPreviewButton = async (page: Page) => {
  const goToPreviewButton = page.getByRole('button', { name: 'Esikatseluun' });
  await goToPreviewButton.click();
};

export const saveAsDraft = async (page: Page) => {
  const saveAsDraftButton = page.getByRole('button', { name: 'Tallenna keskeneräisenä' });
  await saveAsDraftButton.click();
};

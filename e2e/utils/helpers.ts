import { Page, expect } from '@playwright/test';
import { UserInputData } from './types';

const checkErrorNofification = async (page: Page) => {
  let errorText = '';
  const errorNotification = page.locator('form .hds-notification--error');
  const errorNotificationVisible = await errorNotification.isVisible();

  if (errorNotificationVisible) {
    const errorMessage = (await errorNotification.textContent()) ?? '';
    errorText = errorMessage.trim() ?? 'Application preview page contains errors';
  }

  expect(errorNotificationVisible, errorText).toBeFalsy();
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

export const submitApplication = async (page: Page) => {
  await expect.soft(page.getByText('Tarkista lähetyksesi')).toBeVisible();
  await checkErrorNofification(page);
  await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita').check();
  await page.getByRole('button', { name: 'Lähetä' }).click();
  await expect(page.getByRole('heading', { name: 'Avustushakemus lähetetty onnistuneesti' })).toBeVisible();
};

export const expectApplicationToBeOpen = async (page: Page) => {
  await expect(page.getByText('Application is not open')).not.toBeVisible();
  await expect(page.getByText('The website encountered an unexpected error')).not.toBeVisible();
};

export const checkConfirmationPage = async (page: Page, userInputData: UserInputData) => {
  const previewText = await page.locator('table').innerText();
  Object.values(userInputData).forEach((value) => expect.soft(previewText).toContain(value));
};

export const applicationIsReceivedSuccesfully = async (page: Page) => {
  await expect(page.getByText('Lähetetty - odotetaan vahvistusta').first()).toBeVisible();
  await expect(page.getByText('Vastaanotettu', { exact: true })).toBeVisible({ timeout: 120 * 1000 });
};

export const checkSentApplication = async (page: Page, userInputData: UserInputData) => {
  await page.getByRole('link', { name: 'Katsele hakemusta' }).click();

  await expect.soft(page.getByRole('heading', { name: 'Hakemuksen tiedot' })).toBeVisible();
  await expect.soft(page.getByRole('link', { name: 'Tulosta hakemus' })).toBeVisible();
  await expect.soft(page.getByRole('link', { name: 'Kopioi hakemus' })).toBeVisible();

  const applicationData = await page.locator('.webform-submission').innerText();

  Object.values(userInputData).forEach((value) => expect.soft(applicationData).toContain(value));
};

export const sendMessageToApplication = async (page: Page, message: string) => {
  await page.getByLabel('Viesti').fill(message);
  await page.getByRole('button', { name: 'Lähetä' }).click();
  await expect.soft(page.getByLabel('Notification').getByText('Viestisi on lähetetty.')).toBeVisible();
  const submissionMessages = await page.locator('.webform-submission-messages').innerText();
  expect(submissionMessages).toContain(message);
};

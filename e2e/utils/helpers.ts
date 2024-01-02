import { Page, expect } from '@playwright/test';

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

import { type Page, expect } from '@playwright/test';

type ConstructorValues = {
  errorTexts?: string[];
  userInputData?: {
    email: string;
    name: string;
    phoneNumber: string;
  };
  url: string;
};

export abstract class Application {
  protected readonly page;
  protected userInputData;
  protected readonly errorTexts;
  protected readonly url;

  constructor(page: Page, values: ConstructorValues) {
    this.page = page;
    this.errorTexts = values.errorTexts;
    this.url = values.url;
    this.userInputData = values.userInputData;
  }

  async goto() {
    // Open application and check for errors
    await this.page.goto(this.url);
    await expect(this.page.getByText('Application is not open')).not.toBeVisible();
    await expect(this.page.getByText('The website encountered an unexpected error')).not.toBeVisible();
  }

  // Form Interaction Methods
  clickContinueButton = async () => await this.page.getByRole('button', { name: 'Seuraava' }).click();
  clickGoToPreviewButton = async () => await this.page.getByRole('button', { name: 'Esikatseluun' }).click();

  clickEveryStep = async () => {
    const stepperListItems = await this.page.locator('.grants-stepper__steps').all();

    for (const stepperItem of stepperListItems) {
      await stepperItem.click();
      try {
        await this.page.waitForEvent('domcontentloaded'); // TODO: Flaky method?
      } catch (error) {
        throw Error('Failed while clicking on all grants stepper buttons');
      }
    }
  };

  // Form Validation Methods
  checkConfirmationPage = async () => {
    if (!this.userInputData) return;

    const previewText = await this.page.locator('table').innerText();
    Object.values(this.userInputData).forEach((value) => expect.soft(previewText).toContain(value));
  };

  checkErrorNotification = async () => {
    const errorNotificationLocator = this.page.locator('form .hds-notification--error');
    const errorNotificationVisible = await errorNotificationLocator.isVisible();

    if (errorNotificationVisible) {
      const errorNotificationTextContent = (await errorNotificationLocator.textContent()) ?? '';
      const trimmedErrorText = errorNotificationTextContent.trim() ?? 'Application preview page contains errors';
      expect(errorNotificationVisible, trimmedErrorText).toBeFalsy();
    }
  };

  checkErrorTexts = async () => {
    const errorNotificationText = await this.page.locator('.container').locator('.hds-notification--error').innerText();
    this.errorTexts?.forEach((t) => expect.soft(errorNotificationText).toContain(t));
  };

  // Submission Methods
  submitApplication = async () => {
    await expect.soft(this.page.getByText('Tarkista lähetyksesi')).toBeVisible();
    await this.checkErrorNotification();
    await this.page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita').check();
    await this.page.getByRole('button', { name: 'Lähetä' }).click();
    await expect(this.page.getByRole('heading', { name: 'Avustushakemus lähetetty onnistuneesti' })).toBeVisible();
  };

  applicationIsReceivedSuccesfully = async () => {
    await expect(this.page.getByText('Lähetetty - odotetaan vahvistusta').first()).toBeVisible();
    await expect(this.page.getByText('Vastaanotettu', { exact: true })).toBeVisible({ timeout: 120 * 1000 });
  };

  checkSentApplication = async () => {
    await this.page.getByRole('link', { name: 'Katsele hakemusta' }).click();

    await expect.soft(this.page.getByRole('heading', { name: 'Hakemuksen tiedot' })).toBeVisible();
    await expect.soft(this.page.getByRole('link', { name: 'Tulosta hakemus' })).toBeVisible();
    await expect.soft(this.page.getByRole('link', { name: 'Kopioi hakemus' })).toBeVisible();

    const applicationData = await this.page.locator('.webform-submission').innerText();

    if (this.userInputData) {
      Object.values(this.userInputData).forEach((value) => expect.soft(applicationData).toContain(value));
    }
  };

  submitApplicationAndCheckSentApplication = async () => {
    await this.submitApplication();
    await this.applicationIsReceivedSuccesfully();
    await this.checkSentApplication();
  };

  // Draft Handling Methods
  saveAsDraft = async () => {
    const saveAsDraftButton = this.page.getByRole('button', { name: 'Tallenna keskeneräisenä' });
    await saveAsDraftButton.click();
    // Check application draft page
    await expect.soft(this.page.getByText('Luonnos')).toBeVisible();
    await expect.soft(this.page.getByRole('link', { name: 'Muokkaa hakemusta' })).toBeEnabled();
    const applicationId = await this.page.locator('.webform-submission__application_id--body').innerText();
    const pageText = await this.page.getByLabel('1. Hakijan tiedot').innerText();

    if (this.userInputData) {
      const textsToCheck = [this.userInputData.email, this.userInputData.name, this.userInputData.phoneNumber];
      textsToCheck.forEach((t) => expect.soft(pageText).toContain(t));
    }

    // Check if application is shown in "Keskeneräiset hakemukset"
    await this.page.goto('fi/oma-asiointi');
    const drafts = await this.page.locator('#oma-asiointi__drafts').innerText();
    expect.soft(drafts).toContain(applicationId);
  };

  saveAndRemoveDraft = async () => {
    await this.page.getByRole('button', { name: 'Tallenna keskeneräisenä' }).click();
    await expect(this.page.getByText('Hakemuksen tiedot')).toBeVisible();
    await this.page.getByRole('link', { name: 'Muokkaa hakemusta' }).click();
    await expect(this.page.getByText('Avustuksen tiedot')).toBeVisible();
    await this.page.getByRole('link', { name: 'Poista luonnos' }).click();
    await expect(this.page.getByText('Luonnos poistettu')).toBeVisible({ timeout: 60 * 1000 });
  };

  // Messaging Methods
  sendMessageToApplication = async () => {
    const message = 'TODO TODO';
    await this.page.getByLabel('Viesti').fill(message);
    await this.page.getByRole('button', { name: 'Lähetä' }).click();
    await expect.soft(this.page.getByLabel('Notification').getByText('Viestisi on lähetetty.')).toBeVisible();
    const submissionMessages = await this.page.locator('.webform-submission-messages').innerText();
    expect(submissionMessages).toContain(message);
  };
}

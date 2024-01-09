import { expect, test as base } from '@playwright/test';
import { selectRole } from '../../utils/role';
import { ApplicationA } from './pom/application_A';
import { faker } from '@faker-js/faker';

// Taiteen perusopetuksen avustukset
const inputData = {
  additionalInformation: faker.lorem.words(),
  attachmentInfo: faker.lorem.words(),
  email: 'test@example.org',
  name: faker.person.fullName(),
  phoneNumber: faker.phone.number(),
  shortDescription: faker.lorem.words(),
  subventionAmount: '123,00€',
};

export type ApplicationA_InputData = typeof inputData;

// Extend basic test by providing a fixture.
const test = base.extend<{ application: ApplicationA }>({
  application: async ({ page }, use) => {
    // Set up the fixture.
    await selectRole(page, 'REGISTERED_COMMUNITY');
    const application = new ApplicationA(page, { ...inputData });
    await application.goto();

    // Use the fixture value in the test.
    await use(application);
  },
});

test.only('Submit application and send message', async ({ application }) => {
  await application.fillAllSteps();
  await application.checkConfirmationPage();
  await application.submitApplicationAndCheckSentApplication();
  await application.sendMessageToApplication();
});

test('Application can be saved as a draft', async ({ application }) => {
  await application.fillStep_1();
  await application.saveAndCheckDraft();
});

test('Draft can be removed', async ({ application }) => {
  await application.fillStep_1();
  await application.saveAndRemoveDraft();
});

test('Check errors for required fields', async ({ application }) => {
  await application.clickEveryStep();
  await application.checkErrorTexts();
});

test('Invalid email', async ({ application, page }) => {
  application.userInputData.email = 'porkkana@keitto';

  await application.fillStep_1();
  await expect(page.getByText('Hakemusta koskeva sähköposti kenttä ei ole oikeassa muodossa')).toBeVisible();
});

test('Missing subvention amount', async ({ application, page }) => {
  application.userInputData.subventionAmount = '';

  await application.fillStep_1();
  await application.fillStep_2();
  await expect(page.getByText('Sinun on syötettävä vähintään yhdelle avustuslajille summa')).toBeVisible();
});

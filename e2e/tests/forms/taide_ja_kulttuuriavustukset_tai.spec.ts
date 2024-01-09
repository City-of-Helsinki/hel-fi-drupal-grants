import { Page, expect, test } from '@playwright/test';
import { selectRole } from '../../utils/role';
import { ApplicationA } from './pom/application_A';

test.describe('Taiteen perusopetuksen avustukset', () => {
  let page: Page;
  let application: ApplicationA;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    application = new ApplicationA(page);
    await selectRole(page, 'REGISTERED_COMMUNITY');
  });

  test.beforeEach(async () => {
    await application.goto();
  });

  test('Submit application and send message', async () => {
    await application.fillAllSteps();
    await application.checkConfirmationPage();
    await application.submitApplicationAndCheckSentApplication();
    await application.sendMessageToApplication();
  });

  test('Application can be saved as a draft', async () => {
    await application.fillStep_1();
    await application.saveAsDraft();
  });

  test('Draft can be removed', async () => {
    await application.fillStep_1();
    await application.saveAndRemoveDraft(); // TODO: Combine with "save as draft" test
  });

  test('Check errors for required fields', async () => {
    await application.clickEveryStep();
    await application.checkErrorTexts();
  });

  test('Invalid email', async () => {
    application.userInputData.email = 'porkkana on hyvää'; // TODO: Immutable
    await application.fillStep_1();
    await expect(page.locator('[data-webform-key="1_hakijan_tiedot"]')).toBeVisible(); // TODO: Rewrite (check that user stays on current page)
  });
});

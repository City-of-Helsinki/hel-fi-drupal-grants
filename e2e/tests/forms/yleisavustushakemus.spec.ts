import { test } from '@playwright/test';
import { ApplicationB } from './pom/application_B';

test('Kaupunginhallitus, yleisavustushakemus', async ({ page }) => {
  const application = new ApplicationB(page);

  await application.goto();
  await application.fillAllSteps();
  await application.submitApplication();
});

import { test } from '@playwright/test';
import { ApplicationB } from './pom/application_B';
import { selectRole } from '../../utils/role';

test(`Kaupunginhallitus, yleisavustushakemus`, async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');

  const application = new ApplicationB(page);

  await application.goto();
  await application.fillAllSteps();
  await application.submitApplication();
});

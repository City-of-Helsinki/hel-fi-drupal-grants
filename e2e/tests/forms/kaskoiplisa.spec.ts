import { test } from '@playwright/test';
import { ApplicationC } from './pom/application_C';
import { selectRole } from '../../utils/role';

test(`Iltapäivätoiminnan harkinnanvarainen lisäavustushakemus`, async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  const application = new ApplicationC(page);

  await application.goto();
  await application.fillAllSteps();
  await application.submitApplication();
});

import { test } from '@playwright/test';
import { ApplicationE } from './pom/application_E';
import { selectRole } from '../../utils/role';

test(`Taide- ja kulttuuriavustukset: projektiavustukset`, async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');

  const application = new ApplicationE(page);

  await application.goto();
  await application.fillAllSteps();
  await application.submitApplication();
});

import path from 'path';
import { test } from '@playwright/test';
import { executeFormFlow } from "../../../utils/react/formFlow";

const FORM_ID = path.basename(__dirname).replace(/^\d+_/, '');

test(`Execute the form test flow for: ${FORM_ID}`, async ({ page }) => {
  await executeFormFlow(page, FORM_ID);
});

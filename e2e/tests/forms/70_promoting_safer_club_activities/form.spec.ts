import path from 'path';
import { test } from '@playwright/test';
import { Role } from '../../../utils/auth_helpers';
import { executeFormFlow } from "../../../utils/react/formFlow";

const FORM_ID = path.basename(__dirname).replace(/^\d+_/, '');
const FORM_ROLE:Role = 'REGISTERED_COMMUNITY';

test(`Execute the form test flow for: ${FORM_ID}`, async ({ page }) => {
  await executeFormFlow(page, FORM_ID, FORM_ROLE);
});

import path from 'path';
import { test } from '@playwright/test';
import { Role } from '../../../utils/auth_helpers';
import { executeFormFlow, verifyFormAccessAsDraft } from "../../../utils/react/formFlow";

const FORM_ID = path.basename(__dirname).replace(/^\d+_/, '');

test(`Execute the form test flow for: ${FORM_ID}`, async ({ page }) => {
  await executeFormFlow(page, FORM_ID, 'REGISTERED_COMMUNITY');
});

test(`Access and draft as unregistered community: ${FORM_ID}`, async ({ page }) => {
  await verifyFormAccessAsDraft(page, FORM_ID, 'UNREGISTERED_COMMUNITY');
});

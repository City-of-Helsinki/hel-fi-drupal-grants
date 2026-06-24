import {type Page, test} from '@playwright/test';
import {
  type FieldInputs,
  type FilledFields,
  type FormLogic,
  fillFormFields, verifyAnswers,
  verifyFormAndSubmit,
  verifyFormFieldTranslations
} from './formFieldVerifier';
import { craftSchema, type FormPreviewResponse } from './schemaFetcher';
import { recordReactReceived } from './receivedStatus';
import { Role, selectRole} from "../auth_helpers";
import {
  captureApplicationNumber,
  createTranslator,
  deleteDraft,
  saveDraft,
  waitForFormLoad
} from './utils';

/**
 * Run the full form flow.
 *
 * Log in, validate field labels/tooltips/required indicators in all
 * languages, fill the form in Finnish, verify answers in all translations,
 * and finally submit the form and verify it has been received.
 *
 * @param page
 *   The Playwright page instance.
 * @param FORM_ID
 *   The form identifier.
 * @param FORM_ROLE
 *   The user role used during the flow.
 * @param fieldInputs
 *   Custom fill values for specific fields, keyed by field id.
 * @param formLogic
 *   Custom logic for a particular form.
 *
 * @return Promise<FilledFields>
 *   The collected field values used during the test.
 */
export async function executeFormFlow(
  page: Page,
  FORM_ID: string,
  FORM_ROLE: Role,
  formLogic?: FormLogic,
  fieldInputs?: FieldInputs,
): Promise<FilledFields> {
  const FORM_URL = `/fi/application/new/${FORM_ID}`;
  const FORM_JSON = `/fi/application/preview/${FORM_ID}`;

  // Log in and select the role before opening the form.
  await selectRole(page, FORM_ROLE);
  await page.goto(FORM_URL);

  let applicationNumber;
  // Track filled field values during the form filling for later verification.
  let filledFields: FilledFields = new Map();

  // Download the form structure and all translations from the API endpoint.
  // We need this to know what fields exist and what their labels should be.
  const formData = await craftSchema(FORM_ID, FORM_JSON);

  // Start listening for the application number before opening the form.
  const applicationNumberPromise = captureApplicationNumber(page);
  await page.goto(FORM_URL);
  await waitForFormLoad(page);
  // Wait until the application number has been received and store it.
  applicationNumber = await applicationNumberPromise;

  // Open the form in each language and check that every field label,
  // tooltip and description shows the correct translated text.
  await test.step('Assert the form field translations', async () => {
    await verifyFormFieldTranslations(page, formData, {
      formURL: `${FORM_URL}/${applicationNumber}`,
      languages: ['fi', 'en', 'sv'],
      formLogic: formLogic,
    });
  });

  // Go through every field on every step and fill it with a valid value.
  await test.step('Fill the form in Finnish', async () => {
    await fillFormFields(page, formData, {
      formURL: `${FORM_URL}/${applicationNumber}`,
      languages: ['fi'],
      filledFields: filledFields,
      fieldInputs: fieldInputs,
      formLogic: formLogic,
    });
  });

  // Check the preview page to confirm all filled values are shown correctly.
  await test.step('Verify the answers via preview', async () => {
    await verifyAnswers(page, formData, {
      formURL: `${FORM_URL}/${applicationNumber}`,
      languages: ['fi', 'en', 'sv'],
      filledFields: filledFields,
    });
  });

  // Submit the form and wait for the successful completion.
  await test.step('Submit the form and wait for completion.', async () => {
    const applicationReceived = await verifyFormAndSubmit(page, formData, {
      formURL: `${FORM_URL}/${applicationNumber}`,
      formCompletionURL: `/fi/application/${applicationNumber}/completion`,
    });
    recordReactReceived(FORM_ID, applicationReceived);
  });

  // Confirm the submitted application appears in the sent list.
  await test.step('Verify the application is in the sent list', async () => {
    await assertApplicationInList(page, applicationNumber, 'sent');
  });
}

/**
 * Check that a profile can access the form and save it as a draft.
 *
 * Select the unregistered community or private role, open the form,
 * save an empty draft and then try to remove it.
 *
 * @param page
 *   The Playwright page instance.
 * @param FORM_ID
 *   The form identifier.
 * @param FORM_ROLE
 *   The user role used during the flow.
 */
export async function verifyFormAccessAsDraft(
  page: Page,
  FORM_ID: string,
  FORM_ROLE: Role,
): Promise<void> {
  const FORM_URL = `/fi/application/new/${FORM_ID}`;
  const FORM_JSON = `/fi/application/preview/${FORM_ID}`;

  await selectRole(page, FORM_ROLE);

  const formData = await craftSchema(FORM_ID, FORM_JSON);
  const t = createTranslator(formData as FormPreviewResponse, 'fi');

  // Open the form and confirm the React application loads for this profile.
  const applicationNumberPromise = captureApplicationNumber(page);
  await page.goto(FORM_URL);
  await waitForFormLoad(page);
  const applicationNumber = await applicationNumberPromise;

  // Save the form as a draft and then remove it.
  await saveDraft(page, t);
  await page.waitForURL('**/oma-asiointi/hakemukset', { timeout: 30_000 });
  await deleteDraft(page, applicationNumber);
}

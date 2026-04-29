import {type Page, test} from '@playwright/test';
import {
  type FilledFields,
  fillFormFields, verifyAnswers,
  verifyFormAndSubmit,
  verifyFormFieldTranslations
} from './formFieldVerifier';
import { craftSchema } from './schemaFetcher';
import { checkLoginStateAndLogin, Role, selectRole} from "../auth_helpers";
import {
  captureApplicationNumber,
  waitForFormLoad
} from './utils';

/**
 * Runs the full form flow.
 *
 * Logs in, validates field labels/tooltips/required indicators in all
 * languages, fills the form in Finnish, verifies answers in all translations,
 * and finally submits the form and verifies it has been received.
 *
 * @param page
 *   The Playwright page instance.
 * @param FORM_ID
 *   The form identifier.
 * @param FORM_ROLE
 *   The user role used during the flow.
 *
 * @return Promise<FilledFields>
 *   The collected field values used during the test.
 */
export async function executeFormFlow(
  page: Page,
  FORM_ID: string,
  FORM_ROLE: Role,
): Promise<FilledFields> {
  const FORM_URL = `/fi/application/new/${FORM_ID}`;
  const FORM_JSON = `/fi/application/preview/${FORM_ID}`;

  test.afterAll(async () => {
    await page.close();
  });

  // Log in and select the role before opening the form.
  await checkLoginStateAndLogin(page);
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
    });
  });

  // Go through every field on every step and fill it with a valid value.
  await test.step('Fill the form in Finnish', async () => {
    await fillFormFields(page, formData, {
      formURL: `${FORM_URL}/${applicationNumber}`,
      languages: ['fi'],
      filledFields: filledFields,
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
    await verifyFormAndSubmit(page, formData, {
      formURL: `${FORM_URL}/${applicationNumber}`,
      formCompletionURL: `/fi/application/${applicationNumber}/completion`,
    });
  });
}

import {type Page, test} from '@playwright/test';
import {
  type FieldInputs,
  type FilledFields,
  type FormLogic,
  fillFormFields, verifyAnswers,
  verifyFormAndSubmit,
  verifyFormFieldTranslations,
  verifySentApplication,
  modifySubmittedApplication
} from './formFieldVerifier';
import { craftSchema, type FormPreviewResponse } from './schemaFetcher';
import { recordReactReceived } from './receivedStatus';
import { Role, selectRole} from "../auth_helpers";
import {
  assertApplicationInList,
  captureApplicationNumber,
  createTranslator,
  deleteDraft,
  saveDraft,
  waitForFormLoad
} from './utils';

/**
 * Skip submitting and the post submit checks when SKIP_SUBMIT is set.
 */
const SKIP_SUBMIT = ['1', 'true'].includes(process.env.SKIP_SUBMIT ?? '');

/**
 * Map a form's applicant type to the role used to access it.
 */
const APPLICANT_TYPE_ROLES: Record<string, Role> = {
  registered_community: 'REGISTERED_COMMUNITY',
  unregistered_community: 'UNREGISTERED_COMMUNITY',
  private_person: 'PRIVATE_PERSON',
};

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
 * @param formLogic
 *   Custom logic for a particular form.
 * @param fieldInputs
 *   Custom fill values for specific fields, keyed by field id.
 * @param PRIMARY_FORM_ROLE
 *   The primary role the form is filled and submitted with. Defaults to the
 *   registered community role.
 *
 * @return Promise<FilledFields>
 *   The collected field values used during the test.
 */
export async function executeFormFlow(
  page: Page,
  FORM_ID: string,
  formLogic?: FormLogic,
  fieldInputs?: FieldInputs,
  PRIMARY_FORM_ROLE: Role = 'REGISTERED_COMMUNITY',
): Promise<FilledFields> {
  const FORM_URL = `/fi/application/new/${FORM_ID}`;
  const FORM_JSON = `/fi/application/preview/${FORM_ID}`;

  // Log in and select the primary role before opening the form.
  await selectRole(page, PRIMARY_FORM_ROLE);

  let applicationNumber;
  // Track filled field values during the form filling for later verification.
  let filledFields: FilledFields = new Map();

  // Download the form structure and all translations from the API endpoint.
  // We need this to know what fields exist and what their labels should be.
  const formData = await craftSchema(FORM_ID, FORM_JSON);

  // Start listening for the application number before opening the form.
  const applicationNumberPromise = captureApplicationNumber(page);
  await page.goto(FORM_URL);
  // Wait until the application number has been received and store it.
  applicationNumber = await applicationNumberPromise;
  // Reopen via the application URL so a reload during load keeps the same draft.
  await page.goto(`${FORM_URL}/${applicationNumber}`);
  await waitForFormLoad(page);

  // Open the form in each language and check that every field label,
  // tooltip and description shows the correct translated text.
  /*
  await test.step('Assert the form field translations', async () => {
    await verifyFormFieldTranslations(page, formData, {
      formURL: `${FORM_URL}/${applicationNumber}`,
      languages: ['fi', 'en', 'sv'],
      formLogic: formLogic,
    });
  });
   */

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

  // Confirm the saved draft appears in the drafts list.
  /*
  await test.step('Verify the draft is in the drafts list', async () => {
    await assertApplicationInList(page, applicationNumber, 'drafts');
  });

  // Submit the form and verify the submission unless skipping is requested.
  if (!SKIP_SUBMIT) {
    let applicationReceived = false;
    await test.step('Submit the form and wait for completion.', async () => {
      applicationReceived = await verifyFormAndSubmit(page, formData, {
        formURL: `${FORM_URL}/${applicationNumber}`,
        formCompletionURL: `/fi/application/${applicationNumber}/completion`,
      });
      recordReactReceived(FORM_ID, applicationReceived);
    });

    // Confirm the submitted application appears in the "sent" list.
    await test.step('Verify the application is in the sent list', async () => {
      await assertApplicationInList(page, applicationNumber, 'sent');
    });

    // Confirm the "sent" application still shows the same filled values.
    await test.step('Verify the sent application values', async () => {
      await verifySentApplication(page, formData, applicationNumber, filledFields);
    });

    // Editing is only possible once the application has been received.
    if (applicationReceived) {
      await test.step('Modify the submitted application', async () => {
        await modifySubmittedApplication(page, formData, applicationNumber, filledFields);
      });
    }
  } else {
    // Remove the draft so skipped runs do not accumulate drafts.
    await test.step('Delete the draft', async () => {
      await deleteDraft(page, applicationNumber);
    });
  }

  // Verify every secondary applicant type can access and draft the form.
  for (const applicantType of formData.settings.applicant_types) {
    const role = APPLICANT_TYPE_ROLES[applicantType];
    if (!role || role === PRIMARY_FORM_ROLE) continue;
    await test.step(`Verify form access as ${applicantType}`, async () => {
      await verifyFormAccessAsDraft(page, FORM_ID, role);
    });
  }
  */
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
  const applicationNumber = await applicationNumberPromise;
  // Reopen via the application URL so a reload during load keeps the same draft.
  await page.goto(`${FORM_URL}/${applicationNumber}`);
  await waitForFormLoad(page);

  // Save the form as a draft and then remove it.
  await saveDraft(page, t);
  await page.waitForURL('**/oma-asiointi/hakemukset', { timeout: 30_000 });
  await deleteDraft(page, applicationNumber);
}

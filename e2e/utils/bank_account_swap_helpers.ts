import {expect, Page, test} from "@playwright/test";
import {FormData, FormField, Selector} from "./data/test_data";
import {logger} from "./logger";
import {fillFormField} from "./form_helpers";
import {PROFILE_INPUT_DATA} from "./data/profile_input_data";

/**
 * The swapBankAccounts function.
 *
 * This function tests bank account swapping. This id done by:
 *
 * @param formKey
 *   The form variant we are swapping bank accounts for.
 * @param profileType
 *   The profile type.
 * @param formId
 *   The form ID.
 * @param page
 *   Page object from Playwright.
 * @param formDetails
 *   The form data of the form we are swapping account for.
 * @param storedata
 *   The env form data.
 */
const swapBankAccounts = async (
  formKey: string,
  profileType: string,
  formId: string,
  page: Page,
  formDetails: FormData,
  storedata: any
) => {

  if (storedata === undefined || storedata[formKey] === undefined) {
    logger(`Skipping bank account swap test: No env data stored after the "${formDetails.title}" test.`);
    test.skip(true, 'Skip bank account swap test');
    return;
  }

  const {applicationId, submissionUrl} = storedata[formKey];
  const {iban, iban2} = PROFILE_INPUT_DATA;

  if (!iban || !iban2) {
    logger('Skipping bank account swap test: Iban values not set in PROFILE_INPUT_DATA.');
    test.skip(true, 'Skip bank account swap test');
    return;
  }

  logger(`Performing bank account swap test: Application ID: ${applicationId}. Bank accounts: ${iban}, ${iban2}...`)

  // Swap to the new IBAN.
  await navigateToApplicantDetailsPage(page, submissionUrl);
  await performBankAccountSwap(page, iban2);

  // Swap back to the original IBAN.
  await navigateToApplicantDetailsPage(page, submissionUrl);
  await performBankAccountSwap(page, iban);
}

/**
 * The performBankAccountSwap function.
 *
 * @param page
 * @param iban
 */
const performBankAccountSwap = async (page: Page, iban: string) => {
  logger(`Swapping to bank account: ${iban}.`);

  const bankAccountField: FormField = {
    role: 'select',
    selector: {
      type: 'by-label',
      name: '',
      value: 'edit-bank-account-account-number-select',
    },
    value: iban,
  };

  await fillFormField(page, bankAccountField, 'edit-bank-account-account-number-select');
  await saveDraftAndVerifyBankAccount(page, iban);
};

/**
 * The navigateToApplicantDetailsPage function.
 *
 * @param page
 * @param submissionUrl
 */
const navigateToApplicantDetailsPage = async (page: Page, submissionUrl: string) => {
  await page.goto(submissionUrl);
  await page.waitForURL('**/muokkaa');
  await page.locator('[data-webform-page="1_hakijan_tiedot"]').click();
  await page.waitForLoadState('load');
  logger(`Navigated to applicant details page: ${submissionUrl}.`);
};

/**
 * The saveDraftAndVerifyBankAccount function.
 *
 * @param page
 * @param iban
 */
const saveDraftAndVerifyBankAccount = async (page: Page, iban: string) => {
  await page.locator('[data-drupal-selector="edit-actions-draft"]').click();
  await page.waitForURL('**/katso');
  await expect(page.locator('.form-item-bank-account')).toContainText(iban);
  logger(`Bank account swapped to ${iban}.`);
};

export { swapBankAccounts };


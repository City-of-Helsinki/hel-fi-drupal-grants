import {expect, Page, test} from "@playwright/test";
import {FormData, FormField, Selector} from "./data/test_data";
import {logger} from "./logger";
import {clickButton, fillFormField} from "./input_helpers";
import {PROFILE_INPUT_DATA} from "./data/profile_input_data";

/**
 * The swapBankAccounts function.
 *
 * This function tests bank account swapping. This id done by
 * calling performBankAccountSwap twice: First with a new iban
 * (secondaryIban), and then with the original one (primaryIban).
 * On both times after the swap, the IBAN is validated on the
 * "/katso" page of the application by saveDraftAndVerifyBankAccount.
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

  // Get the application ID and submission URL.
  const {applicationId, submissionUrl} = storedata[formKey];

  // Get "primaryIban" (the IBAN every application uses) and the secondary IBAN, "secondaryIban".
  const {iban: primaryIban, iban2: secondaryIban} = PROFILE_INPUT_DATA;

  if (!primaryIban || !secondaryIban) {
    logger('Skipping bank account swap test: Iban values not set in PROFILE_INPUT_DATA.');
    test.skip(true, 'Skip bank account swap test');
    return;
  }

  logger(`Performing bank account swap test for: ${applicationId}...`);
  logger(`Testing with bank accounts: ${primaryIban}, ${secondaryIban}.`)

  // Swap to the new IBAN.
  await performBankAccountSwap(page, secondaryIban, submissionUrl);

  // Swap back to the original IBAN.
  await performBankAccountSwap(page, primaryIban, submissionUrl);
}

/**
 * The performBankAccountSwap function.
 *
 * This function navigate to the application details page
 * of an application by calling navigateToApplicantDetailsPage,
 * and the swaps the selected bank account with the passed
 * in IBAN number. The swap is then validated by calling
 * saveDraftAndVerifyBankAccount.
 *
 * @param page
 *   Page object from Playwright.
 * @param iban
 *   The IBAN we are swapping to.
 * @param submissionUrl
 *   The submission URL of the application.
 */
const performBankAccountSwap = async (page: Page, iban: string, submissionUrl: string) => {
  // Navigate to the application.
  await navigateToApplicantDetailsPage(page, submissionUrl);

  // Set the IBAN in the application.
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

  // Save as draft and verify the swap.
  await saveDraftAndVerifyBankAccount(page, iban);
};

/**
 * The navigateToApplicantDetailsPage function.
 *
 * This function navigate to an applications submission
 * URL and selects the "Applicant details" page.
 *
 * @param page
 *   Page object from Playwright.
 * @param submissionUrl
 *   The submission URL of the application.
 */
const navigateToApplicantDetailsPage = async (page: Page, submissionUrl: string) => {
  const applicantDetailsLink: Selector = {
    type: 'form-topnavi-link',
    name: 'data-webform-page',
    value: '1_hakijan_tiedot',
  }

  await page.goto(submissionUrl);
  await page.waitForURL('**/muokkaa');

  await clickButton(page, applicantDetailsLink);
  await page.waitForLoadState('load');

  logger(`Navigated to applicant details page: ${submissionUrl}.`);
};

/**
 * The saveDraftAndVerifyBankAccount function.
 *
 * This function saves an application as a draft
 * and checks that the passed in IBAN can be located
 * in the following parts of the "/katso" page.
 *
 * 1. The applications attachment list at the top of the page.
 * 2. The bank account section at the start of the application.
 * 3. The "muu liite" section at the bottom of the application.
 *
 * @param page
 *   Page object from Playwright.
 * @param iban
 *   The IBAN we want to validate.
 */
const saveDraftAndVerifyBankAccount = async (page: Page, iban: string) => {
  // Save the application as a draft.
  const saveDraftLink: Selector = {
    type: 'data-drupal-selector',
    name: 'data-drupal-selector',
    value: 'edit-actions-draft',
  }

  await clickButton(page, saveDraftLink);
  await page.waitForURL('**/katso');

  // Make sure the that the new IBAN is located in all three places it should be.
  await expect(page.locator('.application-attachment-list')).toContainText(iban);
  await expect(page.locator('.form-item-bank-account')).toContainText(iban);
  await expect(page.locator('.form-item-muu-liite')).toContainText(iban);
  logger(`Bank account swapped and validated to be; ${iban}.`);
};

export { swapBankAccounts };


import {expect, Page, test} from "@playwright/test";
import {FormData, Selector, FormFieldWithSwap} from "./data/test_data";
import {logger} from "./logger";
import {clickButton, fillFormField} from "./input_helpers";
import cloneDeep from "lodash.clonedeep";

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

  for (const [formPageKey, formPageObject] of Object.entries(formDetails.formPages)) {
    if (!formPageObject.itemsToSwap) continue;
    logger(`Doing swaps on page: ${formPageKey}.`);
    for (const [itemKey, itemField] of Object.entries(formPageObject.itemsToSwap)) {
      let originalItem = itemField;
      let swapItem = cloneDeep(itemField);
      swapItem.value = itemField.swapValue;
      await performBankAccountSwap(page, itemKey, swapItem, submissionUrl, formPageKey);
      await performBankAccountSwap(page, itemKey, originalItem, submissionUrl, formPageKey);
    }
  }
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
 * @param itemKey
 *   The item key we are swapping.
 * @param formField
 *   The IBAN we are swapping to.
 * @param submissionUrl
 *   The submission URL of the application.
 * @param formPageKey
 */
const performBankAccountSwap = async (
  page: Page,
  itemKey: string,
  formField: FormFieldWithSwap,
  submissionUrl: string,
  formPageKey: string,
) => {
  // Navigate to the application.
  await navigateToApplicantDetailsPage(page, submissionUrl, formPageKey);
  logger(`Swapping field values for "${itemKey}" with value "${formField.value}".`);
  await fillFormField(page, formField, itemKey);
  await saveDraftAndVerifyBankAccount(page, itemKey, formField);
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
 * @param formPageKey
 */
const navigateToApplicantDetailsPage = async (page: Page, submissionUrl: string, formPageKey: string) => {
  const applicantDetailsLink: Selector = {
    type: 'form-topnavi-link',
    name: 'data-webform-page',
    value: formPageKey,
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
 * @param itemKey
 * @param formField
 *   The IBAN we want to validate.
 */
const saveDraftAndVerifyBankAccount = async (page: Page, itemKey:string, formField: FormFieldWithSwap) => {
  // Save the application as a draft.
  const saveDraftLink: Selector = {
    type: 'data-drupal-selector',
    name: 'data-drupal-selector',
    value: 'edit-actions-draft',
  }
  await clickButton(page, saveDraftLink);
  await page.waitForURL('**/katso');

  for (const selector of formField.viewPageClasses) {
    if (!formField.value) continue;
    await expect(page.locator(selector)).toContainText(formField.value);
    logger(`Verified ${selector}: ${formField.value}`);
  }
};

export { swapBankAccounts };


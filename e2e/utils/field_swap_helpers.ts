import {Page, test} from "@playwright/test";
import {FieldSwapItemList, FormData, FormPage, Selector} from "./data/test_data";
import {logger} from "./logger";
import {clickButton, fillFormField} from "./input_helpers";
import {validateFormData} from "./validation_helpers";
import {logCurrentUrl} from "./helpers";
import {goToSubmissionUrl, navigateToApplicationPage} from "./navigation_helpers";

/**
 * The swapFieldValues function.
 *
 * This function tests swapping field values.
 * This is done by:
 *
 * 1. Navigating to the submission URL with goToSubmissionUrl.
 * 2. Looping all form pages and checking if itemsToSwap is set.
 * 3. Swapping field values with swapFieldValuesOnPage.
 * 4. Saving the form as draft with saveAsDraft.
 * 5. Validating that the values were changed with validateFormData.
 *
 * @param formKey
 *   The form variant key.
 * @param page
 *   Page object from Playwright.
 * @param formDetails
 *   The form data.
 * @param storedata
 *   The env form data.
 */
const swapFieldValues = async (
  formKey: string,
  page: Page,
  formDetails: FormData,
  storedata: any
) => {
  if (storedata === undefined || storedata[formKey] === undefined) {
    logger(`Skipping field value swap test: No env data stored after the "${formDetails.title}" test.`);
    test.skip(true, 'Skip field value swap test');
    return;
  }

  const {applicationId, submissionUrl} = storedata[formKey];
  logger(`Performing field swap test for application: ${applicationId}...`);
  await goToSubmissionUrl(page, submissionUrl);

  for (const [formPageKey, formPageObject] of Object.entries(formDetails.formPages)) {
    if (!formPageObject.itemsToSwap) continue;
    let itemsToSwap = formPageObject.itemsToSwap;
    await swapFieldValuesOnPage(page, formPageKey, formPageObject, itemsToSwap);
  }

  logger('Validating form with swapped values...');
  await saveAsDraft(page);
  await validateFormData(page, 'viewPage', formDetails);
}

/**
 * The swapFieldValuesOnPage function.
 *
 * This function performs the swapping of field
 * values on a given application page. This is done by:
 *
 * 1. Navigating to the desired application page with navigateToApplicationPage.
 * 2. Swapping the values of the fields inside itemsToSwap
 *    by calling fillFormField after the field values have been manipulated.
 *
 * @param page
 *   Page object from Playwright.
 * @param formPageKey
 *   A form pages key.
 * @param formPageObject
 *   An object containing all the form data.
 * @param itemsToSwap
 *   The items that need to be swapped.
 */
const swapFieldValuesOnPage = async (
  page: Page,
  formPageKey: string,
  formPageObject: FormPage,
  itemsToSwap: FieldSwapItemList
) => {
  await navigateToApplicationPage(page, formPageKey);

  for (const [itemKey, itemField] of Object.entries(formPageObject.items)) {
    const itemToSwap = itemsToSwap.find(item => item.field === itemKey);
    if (!itemToSwap || !itemField.value || !itemField.selector) continue;

    logger(`Swapping field: ${itemKey}`);
    logger(`Original value: ${itemField.value}`);
    logger(`New value: ${itemToSwap.swapValue}`);

    itemField.value = itemToSwap.swapValue;
    await fillFormField(page, itemField, itemKey, true);
  }
};

/**
 * The saveAsDraft function.
 *
 * This function saves and application
 * as draft.
 *
 * @param page
 *   Page object from Playwright.
 */
const saveAsDraft = async (page: Page) => {
  const saveDraftLink: Selector = {
    type: 'data-drupal-selector',
    name: 'data-drupal-selector',
    value: 'edit-actions-draft',
  }
  await clickButton(page, saveDraftLink);
  await logCurrentUrl(page);
  await page.waitForURL('**/oma-asiointi');
  logger('Form saved as draft.')
};

export { swapFieldValues };

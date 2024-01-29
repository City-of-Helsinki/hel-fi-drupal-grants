import {Locator, Page, expect} from "@playwright/test";
import {logger} from "./logger";
import {
  FormField,
  MultiValueField,
  FormData,
  FormPage,
  Selector,
  isMultiValueField,
  DynamicMultiValueField,
  isDynamicMultiValueField
} from "./data/test_data"
import {slowLocator} from "./helpers";
import {viewPageBuildSelectorForItem} from "./view_page_helpers";


const validateSubmission = async (formKey: string, page: Page, formDetails: FormData, storedata: any) => {
  const thisStoreData = storedata[formKey];
  if (thisStoreData.status === 'DRAFT') {
      await validateDraft(page, formDetails, thisStoreData);
  } else {
      await validateSent(page, formDetails, thisStoreData);
  }
}

/**
 * Validate submitted application. Maybe send messages etc.
 *
 * @param page
 * @param formDetails
 * @param thisStoreData
 */
const validateSent = async (page: Page, formDetails: FormData, thisStoreData: any) => {
    logger('Validate RECEIVED', thisStoreData);
}

/**
 * The validateDraft function.
 *
 * This function validates the "/katso" view of an application page.
 * It navigates to the "View" page based on the application ID,
 * and checks that the submitted form data is present on the page.
 *
 * @param page
 * @param formDetails
 * @param thisStoreData
 */
const validateDraft = async (page: Page, formDetails: FormData, thisStoreData: any) => {

  // Navigate to the applications "View" page and make sure we get to it.
  const applicationId = thisStoreData.applicationId;
  const viewPageURL = `/fi/hakemus/${applicationId}/katso`;
  await page.goto(viewPageURL);
  const applicationIdContainer = await page.locator('.webform-submission__application_id');
  const applicationIdContainerText = await applicationIdContainer.textContent();
  expect(applicationIdContainerText).toContain(applicationId);
  logger('Draft validation on page:', viewPageURL);

  // Initialize message containers.
  const skipMessages: string[] = [];
  const noValueMessages: string[] = [];
  const validationSuccesses: string[] = [];
  const validationErrors: string[] = [];

  // Process and validate each form item.
  for (const [formPageKey, formPageObject] of Object.entries(formDetails.formPages)) {
    for (const [itemKey, itemField] of Object.entries(formPageObject.items)) {

      // Skip excluded items.
      if (itemField.viewPageSkipValidation) {
        skipMessages.push(`The item "${itemKey}" has set viewPageSkipValidation to true. Skipping its validation.\n`);
        continue;
      }

      // Skip items that haven't defined a value.
      if (!itemField.value || itemField.value === 'use-random-value') {
        noValueMessages.push(`The item "${itemKey}" has not defined a value. Skipping its validation.\n`);
        continue;
      }

      // Get the item's value and selector.
      let inputValue = itemField.viewPageFormatter ? itemField.viewPageFormatter(itemField.value) : itemField.value;
      let itemSelector = itemField.viewPageSelector ? itemField.viewPageSelector : viewPageBuildSelectorForItem(itemKey);

      // Attempt to locate the item and its text.
      let targetItem, targetItemText
      try {
        targetItem = await page.locator(itemSelector);
        targetItemText = await targetItem.textContent({timeout: 1000});
      } catch (error) {
        validationErrors.push(`\nContent not found on view page:\nITEM KEY IN FORM DATA: ${itemKey}\nUSED ITEM SELECTOR: ${itemSelector}\n`);
        continue;
      }

      // Form the base message for validation results.
      const itemKeyMessage = `\nITEM KEY IN APPLICATION DATA: ${itemKey}`;
      const itemValueMessage = `\nITEM VALUE FROM APPLICATION DATA: ${inputValue}`;
      const itemSelectorMessage = `\nUSED ITEM SELECTOR: ${itemSelector}`;
      const targetTextMessage = `\nFOUND TEXT ON VIEW PAGE: ${targetItemText}`;
      const baseValidationMessage = itemKeyMessage + itemValueMessage + itemSelectorMessage + targetTextMessage

      // Validate the item's text against the input value.
      if (targetItemText && targetItemText.includes(inputValue)) {
        validationSuccesses.push(`\nValidation PASSED:${baseValidationMessage}`);
      } else {
        validationErrors.push(`\nValidation FAILED:${baseValidationMessage}`);
      }
    }
  }

  // Assert no validation errors.
  expect(validationErrors).toEqual([]);

  // Log results.
  logDraftValidationResults(skipMessages, noValueMessages, validationSuccesses)
}

/**
 * The logDraftValidationResults function.
 *
 * This function log messages related to
 * the draft validation process.
 *
 * @param skipMessages
 *   Array of messages for skipped items.
 * @param noValueMessages
 *   Array of messages for items with no values.
 * @param validationSuccesses
 *   Array of detailed messages for successful validations.
 */
const logDraftValidationResults = (
  skipMessages: string[],
  noValueMessages: string[],
  validationSuccesses: string[]
): void => {

  logger(
    `Validation successful!
    Skipped items: ${skipMessages.length}
    No value items: ${noValueMessages.length}
    Validated items: ${validationSuccesses.length} \n`
  );

  // Uncomment if you want more details.
  skipMessages.forEach((msg) => logger(msg));
  noValueMessages.forEach((msg) => logger(msg));
  validationSuccesses.forEach((msg) => logger(msg));
};

export {
  validateSubmission
}

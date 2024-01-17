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
 * Validate application draft view page.
 *
 * @param page
 * @param formDetails
 * @param thisStoreData
 */
const validateDraft = async (page: Page, formDetails: FormData, thisStoreData: any) => {
  const applicationId = thisStoreData.applicationId;
  const viewPageURL = `/fi/hakemus/${applicationId}/katso`;

  // Navigate to the page and make sure we get to it.
  await page.goto(viewPageURL);
  const applicationIdContainer = await page.locator('.webform-submission__application_id');
  const applicationIdContainerText = await applicationIdContainer.textContent();
  expect(applicationIdContainerText).toContain(applicationId);

  // Setup containers for messages.
  const validationErrors: string[] = [];
  const validationSuccesses: string[] = [];
  const skipMessages: string[] = [];
  const noValueMessages: string[] = [];

  // Loop through the input data.
  for (const [formPageKey, formPageObject] of Object.entries(formDetails.formPages)) {
    for (const [itemKey, itemField] of Object.entries(formPageObject.items)) {

      // Skip excluded items (often "next" buttons).
      if (itemField.viewPageSkip) {
        skipMessages.push(`The item "${itemKey}" has set viewPageSkip to true. Skipping its validation. \n`);
        continue;
      }

      // Skip items that haven't defined a value.
      if (!itemField.value || itemField.value === 'use-random-value') {
        noValueMessages.push(`The item "${itemKey}" has not defined a value. Skipping its validation. \n`);
        continue;
      }

      // Get either the raw or formatted input value.
      const inputValue = itemField.viewPageFormatter ? itemField.viewPageFormatter(itemField.value) : itemField.value;

      // Get a class to target the item on the "View" page.
      const itemSelector = itemField.viewPageSelector ? itemField.viewPageSelector : viewPageBuildSelectorForItem(itemKey);

      // Attempt to locate the target and its text.
      let targetItem;
      let targetItemText;
      try {
        targetItem = await page.locator(itemSelector);
        targetItemText = await targetItem.textContent({timeout: 1000});
      } catch (error) {
        validationErrors.push(`Target item not found: \nITEM KEY: ${itemKey} \nITEM SELECTOR: ${itemSelector} \n`);
        continue;
      }

      // Check that the input matches the target text.
      if (targetItemText && targetItemText.includes(inputValue)) {
        validationSuccesses.push(`Validation PASSED: \nITEM KEY: ${itemKey} \nITEM SELECTOR: ${itemSelector} \nITEM VALUE: ${inputValue} \nTARGET TEXT: ${targetItemText} \n`);
      } else {
        validationErrors.push(`Validation FAILED: \nITEM KEY: ${itemKey} \nITEM SELECTOR: ${itemSelector} \nITEM VALUE: ${inputValue} \nTARGET TEXT: ${targetItemText} \n`);
      }
    }
  }

  // Assert that the validation errors array is empty.
  expect(validationErrors).toEqual([]);

  // Log messages as needed.
  skipMessages.forEach((successMessage) => logger(successMessage));
  noValueMessages.forEach((successMessage) => logger(successMessage));
  validationSuccesses.forEach((successMessage) => logger(successMessage));
  logger(`\nValidation successful! \nSkipped items: ${skipMessages.length} \nNo value items: ${noValueMessages.length} \nValidated items: ${validationSuccesses.length} \n`);
}

export {
    validateSubmission
}

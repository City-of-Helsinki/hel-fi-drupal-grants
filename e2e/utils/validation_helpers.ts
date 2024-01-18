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

  // Navigate to the "View" page and make sure we get to it.
  const applicationId = thisStoreData.applicationId;
  const viewPageURL = `/fi/hakemus/${applicationId}/katso`;
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

      // Skip excluded items.
      if (itemField.viewPageSkipValidation) {
        skipMessages.push(`The item "${itemKey}" has set viewPageSkipValidation to true. Skipping its validation. \n`);
        continue;
      }

      // Skip items that haven't defined a value.
      if (!itemField.value || itemField.value === 'use-random-value') {
        noValueMessages.push(`The item "${itemKey}" has not defined a value. Skipping its validation. \n`);
        continue;
      }

      // Get either the raw or formatted input value.
      let inputValue = itemField.viewPageFormatter ? itemField.viewPageFormatter(itemField.value) : itemField.value;

      // Get a class to target the item on the "View" page.
      let itemSelector = itemField.viewPageSelector ? itemField.viewPageSelector : viewPageBuildSelectorForItem(itemKey);

      // Attempt to locate the item and its text.
      let targetItem;
      let targetItemText;
      try {
        targetItem = await page.locator(itemSelector);
        targetItemText = await targetItem.textContent({timeout: 1000});
      } catch (error) {
        validationErrors.push(
          `Target item not found:
          ITEM KEY: ${itemKey}
          ITEM SELECTOR: ${itemSelector} \n`
        );
        continue;
      }

      // Check that the input matches the target text.
      if (targetItemText && targetItemText.includes(inputValue)) {
        validationSuccesses.push(
          `Validation PASSED:
          ITEM KEY: ${itemKey}
          ITEM SELECTOR: ${itemSelector}
          ITEM VALUE: ${inputValue}
          TARGET TEXT: ${targetItemText} \n`
        );
      } else {
        validationErrors.push(
          `Validation FAILED:
          ITEM KEY: ${itemKey}
          ITEM SELECTOR: ${itemSelector}
          ITEM VALUE: ${inputValue}
          TARGET TEXT: ${targetItemText} \n`
        );
      }
    }
  }

  // Assert that the validation errors array is empty.
  expect(validationErrors).toEqual([]);

  // Log messages as needed.
  //skipMessages.forEach((skipMessage) => logger(skipMessage));
  //noValueMessages.forEach((noValueMessage) => logger(noValueMessage));
  //validationSuccesses.forEach((successMessage) => logger(successMessage));
  logger(
    `Validation successful!
    Skipped items: ${skipMessages.length}
    No value items: ${noValueMessages.length}
    Validated items: ${validationSuccesses.length} \n`
  );
}

export {
    validateSubmission
}

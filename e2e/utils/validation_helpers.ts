import {Page, expect} from "@playwright/test";
import {logger} from "./logger";
import {
  FormField,
  FormData,
  FormFieldWithRemove
} from "./data/test_data"
import {viewPageBuildSelectorForItem} from "./view_page_helpers";

/**
 * The validateSubmission function.
 *
 * This function calls either validateDraft or
 * validateSent, depending on the validation we are
 * performing.
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
const validateSubmission = async (
  formKey: string,
  page: Page,
  formDetails: FormData,
  storedata: any
) => {

  const thisStoreData = storedata[formKey];

  if (thisStoreData.status === 'DRAFT') {
    await validateDraft(page, formDetails, thisStoreData);
  } else {
    await validateSent(page, formDetails, thisStoreData);
  }
}

/**
 * The validateSent function.
 *
 * @param page
 *   Page object from Playwright.
 * @param formDetails
 *   The form data.
 * @param thisStoreData
 *   The env form data.
 */
const validateSent = async (
  page: Page,
  formDetails: FormData,
  thisStoreData: any
) => {
  logger('Validate RECEIVED', thisStoreData);
}

/**
 * The validateDraft function.
 *
 * This function validates the "/katso" view of an application page.
 * It navigates to the "View" page based on the application ID,
 * and checks that the submitted form data is present on the page by eventually
 * calling the validateField function.
 *
 * @param page
 *   Page object from Playwright.
 * @param formDetails
 *   The form data.
 * @param thisStoreData
 *   The env form data.
 */
const validateDraft = async (
  page: Page,
  formDetails: FormData,
  thisStoreData: any
) => {

  // Navigate to the applications "View" page and make sure we get to it.
  await navigateAndValidateViewPage(page, thisStoreData);

  // Initialize message containers.
  const skipMessages: string[] = [];
  const noValueMessages: string[] = [];
  const validationErrors: string[] = [];
  const validationSuccesses: string[] = [];

  // Callbacks for message handling.
  const skipMessageCallback = (message: string) => skipMessages.push(message);
  const noValueMessageCallback = (message: string) => noValueMessages.push(message);
  const validationErrorCallback = (message: string) => validationErrors.push(message);
  const validationSuccessCallback = (message: string) => validationSuccesses.push(message);

  // Process and validate each form item.
  for (const [formPageKey, formPageObject] of Object.entries(formDetails.formPages)) {
    for (const [itemKey, itemField] of Object.entries(formPageObject.items)) {

      // Handle dynamic multi-value and multi-value fields by calling validateMultiValueFields
      // that iterates over the fields items.
      if (itemField.role === 'dynamicmultivalue' || itemField.role === 'multivalue') {
        await validateMultiValueFields(
          itemKey, itemField, page,
          skipMessageCallback, noValueMessageCallback,
          validationErrorCallback, validationSuccessCallback
        );
      } else {
        // Normal field validation.
        await validateField(
          itemKey, itemField, page,
          skipMessageCallback, noValueMessageCallback,
          validationErrorCallback, validationSuccessCallback
        );
      }
    }
  }
  // Assert no validation errors.
  expect(validationErrors).toEqual([]);
  // Log results.
  logDraftValidationResults(skipMessages, noValueMessages, validationSuccesses)
}

/**
 * The validateMultiValueFields function.
 *
 * This function iterates over the items in a dynamic
 * multi-value or a multi-value field, and passes the
 * found fields over to validateField for validation.
 *
 * @param itemKey
 *   The item key from the form data.
 * @param itemField
 *   The item field from teh form data.
 * @param page
 *   Page object from Playwright.
 * @param skipMessageCallback
 *   Callback for skipMessages.
 * @param noValueMessageCallback
 *   Callback for noValueMessages.
 * @param validationErrorCallback
 *   Callback for validationErrors.
 * @param validationSuccessCallback
 *   Callback for validationSuccess.
 */
const validateMultiValueFields = async (
  itemKey: string,
  itemField: FormField | FormFieldWithRemove,
  page: Page,
  skipMessageCallback: (message: string) => void,
  noValueMessageCallback: (message: string) => void,
  validationErrorCallback: (message: string) => void,
  validationSuccessCallback: (message: string) => void
) => {

  let multiItemsArray;

  if (itemField.role === 'multivalue' && itemField.multi) {
    multiItemsArray = itemField.multi.items;
  }
  if (itemField.role === 'dynamicmultivalue' && itemField.dynamic_multi) {
    multiItemsArray = itemField.dynamic_multi.multi.items;
  }

  if (!multiItemsArray) return;

  for (const multiItemArray of Object.values(multiItemsArray)) {
    for (const multiItem of multiItemArray) {
      await validateField(
        itemKey, multiItem, page,
        skipMessageCallback, noValueMessageCallback,
        validationErrorCallback, validationSuccessCallback
      );
    }
  }
};

/**
 * The validateField function.
 *
 * This function validates FormFields by checking
 * if their content is present on the current page.
 * The validation is done by:
 *
 * 1. Checking if the field item needs to be skipped.
 * 2. Checking if the field item has a value to validate.
 * 3. Getting the input value for the field item.
 * 4. Getting a selector for the field item.
 * 5. Attempting to locate the selector on the page.
 * 6. Checking if the content of the found selector item
 * matches with the field item input.
 *
 * @param itemKey
 *   The item key from the form data.
 * @param itemField
 *   The item field from teh form data.
 * @param page
 *   Page object from Playwright.
 * @param skipMessageCallback
 *   Callback for skipMessages.
 * @param noValueMessageCallback
 *   Callback for noValueMessages.
 * @param validationErrorCallback
 *   Callback for validationErrors.
 * @param validationSuccessCallback
 *   Callback for validationSuccess.
 */
const validateField = async (
  itemKey: string,
  itemField: FormField | FormFieldWithRemove,
  page: Page,
  skipMessageCallback: (message: string) => void,
  noValueMessageCallback: (message: string) => void,
  validationErrorCallback: (message: string) => void,
  validationSuccessCallback: (message: string) => void
) => {

  // Skip excluded items.
  if (itemField.viewPageSkipValidation) {
    let message = constructMessage(MessageType.SkipValidation, itemKey);
    skipMessageCallback(message);
    return;
  }

  // Skip items that haven't defined a value.
  if (!itemField.value || itemField.value === 'use-random-value') {
    let message = constructMessage(MessageType.NoValue, itemKey);
    noValueMessageCallback(message);
    return;
  }

  // Get the item's input value and format it if viewPageFormatter is defined.
  let rawInputValue = itemField.value
  let formattedInputValue = itemField.viewPageFormatter ? itemField.viewPageFormatter(rawInputValue) : rawInputValue;

  // Get the item's selector.
  let itemSelector = itemField.viewPageSelector ? itemField.viewPageSelector : viewPageBuildSelectorForItem(itemKey);

  // Attempt to locate the item and see if the input value matches the content on the page.
  try {
    const targetItem = await page.locator(itemSelector);
    const targetItemText = await targetItem.textContent({ timeout: 1000 });
    if (targetItemText && targetItemText.includes(formattedInputValue)) {
      validationSuccessCallback(constructMessage(MessageType.ValidationSuccess, itemKey, rawInputValue, formattedInputValue, itemSelector, targetItemText));
    } else {
      validationErrorCallback(constructMessage(MessageType.ValidationError, itemKey, rawInputValue, formattedInputValue, itemSelector, targetItemText));
    }
  } catch (error) {
    validationErrorCallback(constructMessage(MessageType.ContentNotFound, itemKey, rawInputValue, formattedInputValue, itemSelector));
  }
}

/**
 * The navigateAndValidateViewPage function.
 *
 * This function navigates to an applications "View"
 * page and makes sure that we get to it. This is done
 * so that we can validate the input application data
 * against the resulting data on the "View" page.
 *
 * @param page
 *   Page object from Playwright.
 * @param thisStoreData
 *   The env form data.
 */
const navigateAndValidateViewPage = async (
  page: Page,
  thisStoreData: any
) => {

  const applicationId = thisStoreData.applicationId;
  const viewPageURL = `/fi/hakemus/${applicationId}/katso`;
  await page.goto(viewPageURL, {timeout: 10000});
  const applicationIdContainer = await page.locator('.webform-submission__application_id');
  const applicationIdContainerText = await applicationIdContainer.textContent();
  expect(applicationIdContainerText).toContain(applicationId);
  logger('Draft validation on page:', viewPageURL);
}

/**
 * The MessageType enum.
 *
 * This enum defines the types of messages that
 * can be logged when performing application validation.
 */
enum MessageType {
  SkipValidation,
  NoValue,
  ValidationError,
  ValidationSuccess,
  ContentNotFound
}

/**
 * The constructMessage function.
 *
 * This function constructs messages based on the provided
 * message type and the passed in parameters. The messages
 * are used to provide info on the validation process.
 *
 * @param type
 *   The message type.
 * @param itemKey
 *   The item key in the form data.
 * @param rawInputValue
 *   The raw input value from the form data.
 * @param formattedInputValue
 *   The formatted (or un-formatted) value from the form data.
 * @param itemSelector
 *   The used item selector.
 * @param targetItemText
 *   The found text on the page.
 */
const constructMessage = (
  type: MessageType,
  itemKey: string,
  rawInputValue?: string,
  formattedInputValue?: string,
  itemSelector?: string,
  targetItemText?: string | null
): string => {

  switch (type) {
    case MessageType.SkipValidation:
      return `The item (or an item inside of) "${itemKey}" has set viewPageSkipValidation to true. Skipping its validation.\n`;
    case MessageType.NoValue:
      return `The item (or an item inside of) "${itemKey}" has not defined a value. Skipping its validation.\n`;
    case MessageType.ContentNotFound:
      return `Content not found on page:\nItem key in data: ${itemKey}\nRaw value in data: ${rawInputValue}\nFormatted value: ${formattedInputValue}\nUsed selector: ${itemSelector}\n`;
    case MessageType.ValidationError:
      return `Validation FAILED:\nItem key in data: ${itemKey}\nRaw value in data: ${rawInputValue}\nFormatted value: ${formattedInputValue}\nUsed selector: ${itemSelector}\nContent found on page: ${targetItemText}\n`;
    case MessageType.ValidationSuccess:
      return `Validation PASSED:\nItem key in data: ${itemKey}\nRaw value in data: ${rawInputValue}\nFormatted value: ${formattedInputValue}\nUsed selector: ${itemSelector}\nContent found on page: ${targetItemText}\n`;
    default:
      return '';
  }
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
  // skipMessages.forEach((msg) => logger(msg));
  // noValueMessages.forEach((msg) => logger(msg));
  // validationSuccesses.forEach((msg) => logger(msg));
};

export {
  validateSubmission
}

import {Page, expect, test} from "@playwright/test";
import {logger} from "./logger";
import {FormField, FormData, FormFieldWithRemove} from "./data/test_data"
import {viewPageBuildSelectorForItem} from "./view_page_helpers";
import {PROFILE_INPUT_DATA, ProfileInputData} from "./data/profile_input_data";

/**
 * The validateSubmission function.
 *
 * This function is used to validate application submissions.
 * The function calls either validateDraft or
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
  if (storedata === undefined || storedata[formKey] === undefined) {
    logger(`Skipping validation test: No env data stored after the "${formDetails.title}" test.`);
    test.skip(true, 'Skip validation test');
  }

  const thisStoreData = storedata[formKey];
  if (thisStoreData.status === 'DRAFT' || thisStoreData.status === 'RECEIVED') {
    await navigateAndValidateViewPage(page, thisStoreData);
    await validateFormData(page, formDetails);
  }
}

/**
 * The validateProfileData function.
 *
 * This function validates profile data on the
 * "/oma-asiointi/hakuprofiili" page.
 *
 * @param page
 *   Page object from Playwright.
 * @param formDetails
 *   The form data.
 * @param formKey
 *   The form variant key.
 * @param profileType
 *   The profile type we are validating.
 */
const validateProfileData = async (
  page: Page,
  formDetails: FormData,
  formKey: string,
  profileType: string,
) => {
  await navigateAndValidateProfilePage(page, profileType);
  await validateFormData(page, formDetails);
}


/**
 * The validateExistingProfileData function.
 *
 * This function validates only an existing profiles data
 * on the "/oma-asiointi/hakuprofiili" page.
 *
 * The existing profile data originates from
 * PROFILE_INPUT_DATA inside profile_input_data.ts.
 *
 * This data is tested in the situation where a new profile is NOT
 * created when the tests are executed, but we still want to make
 * sure that the profile has the correct information from a previous
 * test.
 *
 * @param page
 *   Page object from Playwright.
 * @param profileType
 *   The profile type we are validating.
 */
const validateExistingProfileData = async (
  page: Page,
  profileType: string,
) => {

  // Grab the hard-coded input data and filter the
  // data depending on the profile type.
  let profileInputData: Partial<ProfileInputData> = PROFILE_INPUT_DATA;

  if (profileType === 'private_person') {
    const privatePersonFields = [
      'iban',
      'iban2',
      'address',
      'zipCode',
      'city',
      'phone'
    ];

    profileInputData = Object.keys(PROFILE_INPUT_DATA)
      .filter((key): key is keyof ProfileInputData => privatePersonFields.includes(key))
      .reduce((obj, key) => {
        obj[key] = PROFILE_INPUT_DATA[key];
        return obj;
      }, {} as Partial<ProfileInputData>);
  }

  // Navigate to the profile page.
  await navigateAndValidateProfilePage(page, profileType);

  // Validate the existing profiles data.
  const profileDataWrapper = await page.locator('.grants-profile');
  const profileData = await profileDataWrapper.textContent();
  const validationErrors: string[] = [];

  if (profileData) {
    for (const [key, value] of Object.entries(profileInputData)) {
      if (!profileData.includes(value)) {
        validationErrors.push( `Profile data "${key}" with value "${value}" not found on profile page.`)
      }
    }
  } else {
    validationErrors.push(`Profile data not found on profile page.`)
  }

  expect(validationErrors).toEqual([]);
  logger('Existing profile data validated.')
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
 */
const validateFormData = async (
  page: Page,
  formDetails: FormData,
) => {

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
  logValidationResults(skipMessages, noValueMessages, validationSuccesses);
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

  // Get the item's selector or selectors.
  let itemSelectors: string[] = [];
  let itemSelector = itemField.viewPageSelector ? itemField.viewPageSelector : viewPageBuildSelectorForItem(itemKey);

  // Check for multiple values inside viewPageSelectors.
  if (itemField.viewPageSelectors) {
    itemSelectors = itemField.viewPageSelectors;
  } else {
    itemSelectors.push(itemSelector);
  }

  // Attempt to locate the item and see if the input value matches the content on the page.
  try {
    for (const selector of itemSelectors) {
      const targetItem = await page.locator(selector);
      const targetItemText = await targetItem.textContent({ timeout: 1000 });

      if (targetItemText && targetItemText.includes(formattedInputValue)) {
        validationSuccessCallback(constructMessage(MessageType.ValidationSuccess, itemKey, rawInputValue, formattedInputValue, selector, targetItemText));
      } else {
        validationErrorCallback(constructMessage(MessageType.ValidationError, itemKey, rawInputValue, formattedInputValue, selector, targetItemText));
      }
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
  await page.goto(viewPageURL);
  await page.waitForURL('**/katso');
  const applicationIdContainer = await page.locator('.webform-submission__application_id');
  const applicationIdContainerText = await applicationIdContainer.textContent();
  expect(applicationIdContainerText).toContain(applicationId);
  logger('Draft validation on page:', viewPageURL);
}

/**
 * The navigateAndValidateProfilePage function.
 *
 * This function navigates to the "Profile"
 * page and makes sure that we get to it. This is done
 * so that we can validate the input profile data
 * against the resulting data on the "Profile" page.
 *
 * @param page
 *   Page object from Playwright.
 * @param profileType
 *   The profile type we are validating.
 */
const navigateAndValidateProfilePage = async (
  page: Page,
  profileType: string
) => {

  const profilePageURL = '/fi/oma-asiointi/hakuprofiili';
  await page.goto(profilePageURL);

  const headingMap: Record<string, string> = {
    registered_community: 'Yhteisön tiedot avustusasioinnissa',
    unregistered_community: 'Yhteisön tai ryhmän tiedot avustusasioinnissa',
    private_person: 'Omat yhteystiedot',
  };

  let expectedHeading = headingMap[profileType];
  let headingContainer = await page.locator('.info-grants');

  await expect(headingContainer, `Failed to locate "${expectedHeading}" on "${profilePageURL}".`).toContainText(expectedHeading);
  logger('Profile data validation on page:', profilePageURL);
}

/**
 * The validateHiddenFields function.
 *
 * This function checks that the passed in items
 * in itemsToBeHidden are not visible on a given page.
 * The functionality is used in tests where the value of
 * field X alters the visibility of field Y.
 *
 * @param page
 *   Page object from Playwright.
 * @param itemsToBeHidden
 *   An array of items that should be hidden.
 * @param formPageKey
 *   The form page we are on.
 */
const validateHiddenFields = async (page: Page, itemsToBeHidden: string[], formPageKey: string) => {
  for (const hiddenItem of itemsToBeHidden) {
    const hiddenSelector = `[data-drupal-selector="${hiddenItem}"]`;
    await expect(page.locator(hiddenSelector), `Field ${hiddenItem} is not hidden on ${formPageKey}.`).not.toBeVisible();
    logger(`Field ${hiddenItem} is hidden on ${formPageKey}.`)
  }
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
 * The logValidationResults function.
 *
 * This function logs messages related to
 * the validation process.
 *
 * @param skipMessages
 *   Array of messages for skipped items.
 * @param noValueMessages
 *   Array of messages for items with no values.
 * @param validationSuccesses
 *   Array of detailed messages for successful validations.
 */
const logValidationResults = (
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
  validateSubmission,
  validateProfileData,
  validateFormData,
  validateExistingProfileData,
  validateHiddenFields,
}

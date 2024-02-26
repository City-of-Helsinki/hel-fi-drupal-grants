import {logger} from "./logger";
import {Page, expect} from "@playwright/test";
import {
  FormData,
  Selector,
  isMultiValueField,
  isDynamicMultiValueField, PageHandlers,
  FormFieldWithRemove
} from "./data/test_data"

import {
  PATH_TO_TEST_PDF,
  saveObjectToEnv,
  extractUrl
} from "./helpers";


/**
 * Set novalidate for given form. This bypasses the browser validations so that
 * our testing methods actually work.
 *
 * Does not work very well on webform forms.
 *
 * @param page
 * @param formClass
 */
async function setNoValidate(page: Page, formClass: string) {
  await page.waitForSelector(`.${formClass}`);
  logger('Set NOVALIDATE called');

  const formHandle = await page.$(`.${formClass}`);

  if (formHandle) {
    const isFormPresent = await formHandle.evaluate((form) => {
      logger('Is Form Present:', form !== null);
      return form !== null;
    });

    if (isFormPresent) {
      logger('Set NOVALIDATE inside formHandle');
      try {
        const result = await formHandle.evaluate((form) => {
          logger('Set NOVALIDATE inside EVALUATE');
          form.setAttribute('novalidate', '');
          return 'Evaluation successful';
        });

        logger('Evaluation Result:', result);
      } catch (error) {
        logger('Error inside evaluate:', error);
      }
    } else {
      logger('Form not found in the DOM');
    }
  }
}

/**
 * Fill form pages from given data array. Calls the pagehandler callbacks for
 * every page set up in formDetails object.
 *
 * If one wants to slow down operations, for example to see what happens with
 * headed test run:
 *
 * <code>
 * ´page.locator = slowLocator(page, 10000);´
 *  </code>
 *
 * @param formKey
 *  Form data key for saving to process.env the application info.
 * @param page
 *  Playwright page object.
 * @param formDetails
 *  Form details object containing all items paged.
 * @param formPath
 *  URL to form. Can be used for checking form validity.
 * @param formClass
 *  Form CSS class, to identify form we're on.
 * @param formID
 * @param profileType
 *  Profile type used for this form. Private, registered..
 * @param pageHandlers
 *  Handler functions for form pages.
 */
const fillGrantsFormPage = async (
  formKey: string,
  page: Page,
  formDetails: FormData,
  formPath: string,
  formClass: string,
  formID: string,
  profileType: string,
  pageHandlers: PageHandlers
) => {

  // Navigate to form url.
  await page.goto(formPath);
  logger('FORM', formPath, formClass);

  // Assertions based on the expected destination
  const initialPathname = new URL(page.url()).pathname;
  const expectedPattern = new RegExp(`^${formDetails.expectedDestination}`);
  expect(initialPathname).toMatch(expectedPattern);

  const applicationId = await getApplicationNumberFromBreadCrumb(page);
  const submissionUrl = await extractUrl(page);

  /**
   * Save info about this application to env. This way they can be deleted
   * via normal DRAFT deleting tests.
   */
  const storeName = `${profileType}_${formID}`;
  const newData = {
    [formKey]: {
      submissionUrl: submissionUrl,
      applicationId,
      status: 'DRAFT'
    }
  }
  saveObjectToEnv(storeName, newData);

  // Loop form pages
  for (const [formPageKey, formPageObject]
    of Object.entries(formDetails.formPages)) {

    // Print out form page we're on.
    logger('Form page:', formPageKey);

    // If we're on the preview page
    if (formPageKey === 'webform_preview') {
      // compare expected errors with actual error messages on the page.
      await validateFormErrors(page, formDetails.expectedErrors);
    }

    const buttons = [];
    // Loop through items on the current page
    for (const [itemKey, itemField]
      of Object.entries(formPageObject.items)) {
      if (itemField.role === 'button') {
        // Collect buttons to be clicked later
        buttons.push(itemField);
      }
    } // end itemField for


    /**
     * If page handler for this form page exists, call that to do the heavy
     * lifting for this page.
     */
    if (pageHandlers[formPageKey]) {
      await pageHandlers[formPageKey](page, formPageObject);
    } else {
      continue;
    }

    /**
     * If we've gathered buttons above, we take the first one and just click it.
     */
    if (buttons.length > 0) {
      const firstButton = buttons[0];
      if (firstButton.selector) {
        await clickButton(page, firstButton.selector, formClass, formPageKey);

        // Here we already are on the new page that is loaded via clickButton

        /**
         * If button is to save draft, then we verify that we got to page we
         * wanted.
         */
        if (firstButton.value === 'save-draft') {
          await verifyDraftSave(
            page,
            formPageKey,
            formPageObject,
            formID,
            profileType,
            submissionUrl,
            formKey
          );
        }
        /**
         * If submit button is clicked, verify that.
         */
        if (firstButton.value === 'submit-form') {
          await verifySubmit(
            page,
            formPageKey,
            formPageObject,
            formID,
            profileType,
            submissionUrl,
            formKey);
        }
      }
    }
  }
}

/**
 * Fills profile form.
 *
 * This was used to develop this concept, hence this is using the original
 * method without any page handlers. This works because custom forms are much
 * more simpler tnan webforms.
 *
 * TODO: Refactor this function to use similar approach than with webforms.
 *
 * @param page
 * @param formDetails
 * @param formPath
 * @param formClass
 */
const fillProfileForm = async (
  page: Page,
  formDetails: FormData,
  formPath: string,
  formClass: string,
) => {

  // Navigate to form url.
  await page.goto(formPath);

  // Assertions based on the expected destination
  // const initialPathname = new URL(page.url()).pathname;
  // expect(initialPathname).toMatch(new RegExp(`^${formDetails.expectedDestination}/?$`));

  // Loop form pages
  for (const [formPageKey, formPageObject]
    of Object.entries(formDetails.formPages)) {
    const buttons = [];
    for (const [itemKey, itemField]
      of Object.entries(formPageObject.items)) {
      if (itemField.role === 'button') {
        // Collect buttons to be clicked later
        buttons.push(itemField);
      } else if (itemField.role === 'multivalue') {
        // Process multivalue fields separately
        await fillMultiValueField(page, itemField, itemKey);
      } else {
        // Or fill simple form field
        await fillFormField(page, itemField, itemKey);
      }
    }

    // Click buttons after filling in the fields
    for (const button of buttons) {
      // @ts-ignore
      await clickButton(page, button.selector);
    }

    await page.waitForLoadState("load");


    // Capture all error messages on the page
    const allErrorElements = await page.$$('.form-item--error-message'); // Adjust selector based on your actual HTML structure
    const actualErrorMessages = await Promise.all(
      allErrorElements.map(async (element) => await element.innerText())
    );

    // Get configured expected errors
    const expectedErrors = Object.entries(formDetails.expectedErrors);
    // If we are not testing error messages
    if (expectedErrors.length === 0) {
      // print errors to stdout
      if (actualErrorMessages.length !== 0) {
        logger('ERRORS', actualErrorMessages);
      }
      // Expect actual error messages size to be 0
      expect(actualErrorMessages.length).toBe(0);
    }

    // Check for expected error messages
    for (const [selector, expectedErrorMessage] of expectedErrors) {
      if (expectedErrorMessage) {
        logger('ERROR', expectedErrorMessage);
        logger('ERRORS', actualErrorMessages);
        // If an error is expected, check if it's present in the captured error messages
        if (typeof expectedErrorMessage === "string") {
          expect(actualErrorMessages.some((msg) => msg.includes(expectedErrorMessage))).toBe(true);
        }
      } else {
        // If no error is expected, check if there are no error messages
        expect(allErrorElements.length).toBe(0);
      }
    }

    // Assertions based on the expected destination
    const actualPathname = new URL(page.url()).pathname;
    const expectedPathname = formDetails.expectedDestination;
    // Check if actualPathname contains the expectedPathname
    expect(actualPathname).toContain(expectedPathname);
  }
};


/**
 * Verify that the application ws indeed saved as a draft. Maybe do data validation in separate test?
 *
 * @param page
 * @param formPageKey
 * @param formPageObject
 * @param formId
 * @param profileType
 * @param submissionUrl
 * @param formKey
 */
const verifyDraftSave = async (
  page: Page,
  formPageKey: string,
  formPageObject: Object,
  formId: string,
  profileType: string,
  submissionUrl: string,
  formKey: string
) => {

// Check application draft page
  await expect(page.getByText('Luonnos')).toBeVisible()
  await expect(page.getByRole('link', {name: 'Muokkaa hakemusta'})).toBeEnabled();
  const applicationId = await page.locator(".webform-submission__application_id--body").innerText();

  const storeName = `${profileType}_${formId}`;
  const newData = {
    [formKey]: {
      submissionUrl: submissionUrl,
      applicationId,
      status: 'DRAFT'
    }
  }
  saveObjectToEnv(storeName, newData);
};

/**
 * Verify that the application got saved to Avus2.
 *
 * @param page
 * @param formPageKey
 * @param formPageObject
 * @param formId
 * @param profileType
 * @param submissionUrl
 * @param formKey
 */
const verifySubmit = async (page: Page,
                            formPageKey: string,
                            formPageObject: Object,
                            formId: string,
                            profileType: string,
                            submissionUrl: string,
                            formKey: string) => {

  await expect(page.getByRole('heading', {name: 'Avustushakemus lähetetty onnistuneesti'})).toBeVisible();
  await expect(page.getByText('Lähetetty - odotetaan vahvistusta').first()).toBeVisible()
  await expect(page.getByText('Vastaanotettu', {exact: true})).toBeVisible({timeout: 90 * 1000})

  let applicationId = await page.locator(".grants-handler__completion__item--number").innerText();
  applicationId = applicationId.replace('Hakemusnumero\n', '')

  const storeName = `${profileType}_${formId}`;
  const newData = {
    [formKey]: {
      submissionUrl: submissionUrl,
      applicationId: applicationId,
      status: 'RECEIVED'
    }
  }
  saveObjectToEnv(storeName, newData);

}

/**
 * Checks form page for errors.
 *
 * @param page
 * @param expectedErrorsArg
 */
const validateFormErrors = async (page: Page, expectedErrorsArg: Object) => {

  // Capture all error messages on the page
  const allErrorElements =
    await page.$$('.hds-notification--error .hds-notification__body ul li');

  // Extract text content from the error elements
  const actualErrorMessages = await Promise.all(
    allErrorElements.map(async (element) => {
      try {
        return await element.innerText();
      } catch (error) {
        logger('Error while fetching text content:', error);
        return '';
      }
    })
  );

  // Get configured expected errors from form PAGE
  const expectedErrors = Object.entries(expectedErrorsArg);
  const expectedErrorsArray = expectedErrors.map(([selector, expectedErrorMessage]) => expectedErrorMessage);

  // If we're not expecting errors...
  if (expectedErrors.length === 0) {
    // Print errors to debug output
    if (actualErrorMessages.length !== 0) {
      console.debug('ERRORS, expected / actual', expectedErrors, actualErrorMessages);
    }
    // Expect actual error messages size to be 0
    expect(actualErrorMessages.length).toBe(0);
  }

  // Check for expected error messages
  const foundErrors: string[] = [];
  const notFoundErrors: string[] = [];
  for (const [selector, expectedErrorMessage] of expectedErrors) {
    if (expectedErrorMessage) {
      // If an error is expected, check if it's present in the captured error messages
      if (actualErrorMessages.some((msg) => msg.includes(<string>expectedErrorMessage))) {
        foundErrors.push(expectedErrorMessage)
      } else {
        notFoundErrors.push(expectedErrorMessage)
      }
    }
  }

  // Make sure that no expected errors are missing.
  if (expectedErrors.length > 0 && notFoundErrors.length !== 0) {
    logger('MISMATCH IN FORM ERRORS!')
    logger('The following errors were expected:', expectedErrors);
    logger('The following errors were found:', foundErrors);
    logger('The following errors are missing:', notFoundErrors);
    expect(notFoundErrors).toEqual([]);
  }

  // Check for unexpected error messages
  const unexpectedErrors = actualErrorMessages.filter(msg => !expectedErrorsArray.includes(msg));
  if (unexpectedErrors.length !== 0) {
    logger('Unexpected errors:', unexpectedErrors);
  }
  // If any unexpected errors are found, the test fails
  expect(unexpectedErrors.length === 0).toBe(true);
}

/**
 * The fillDynamicMultiValueField function.
 *
 * This functions fills dynamic multi-value fields. Dynamic multi-value
 * fields have a radio button that toggles their visibility. After the radio
 * button has been pressed, the field is passed to fillMultiValueField where
 * data entry is performed.
 *
 * @param page
 *   Page object from Playwright.
 * @param formField
 *   The form field from the form data.
 * @param itemKey
 *   Element key in data definition.
 */
const fillDynamicMultiValueField = async (page: Page, formField: Partial<FormFieldWithRemove>, itemKey: string) => {

  // Check that we have the needed dynamic multi-value field.
  if (!isDynamicMultiValueField(formField.dynamic_multi)) {
    logger('A dynamic multi-value field has not been defined in:', itemKey);
    return;
  }

  // Setup constants from the dynamic multi-value field.
  const dynamicMultiValueField = formField.dynamic_multi;
  const radioSelector = dynamicMultiValueField.radioSelector;
  const revealedElementSelector = dynamicMultiValueField.revealedElementSelector;

  // Click the radio button and wait for the multi-value field to appear.
  await fillRadioField(radioSelector, itemKey, page)
  await page.waitForSelector(revealedElementSelector.value ?? '');

  // Fill the multi-value field.
  await fillMultiValueField(page, dynamicMultiValueField, itemKey);
}

/**
 * The fillMultiValueField function.
 *
 * This function fills data to a multi-value field.
 * The fields in each multi-value item are iterated over,
 * updating the items index on each iteration.
 *
 * @param page
 *   Page object from Playwright.
 * @param formField
 *   The form field from the form data.
 * @param itemKey
 *   Element key in data definition.
 */
const fillMultiValueField = async (page: Page, formField: Partial<FormFieldWithRemove>, itemKey: string) => {

  // Check that the dynamic multi-value field has a multi-value field.
  if (!isMultiValueField(formField.multi)) {
    logger('A multi-value field has not been defined in:', itemKey);
    return;
  }

  // Setup constants from the multi-value field.
  const multiValueField = formField.multi;
  const multiValueFieldButtonSelector = multiValueField.buttonSelector;

  // Check if we have an initial item.
  const initialItem = replacePlaceholder('0', '[INDEX]', multiValueFieldButtonSelector.resultValue);
  const initialItemSelector = `[data-drupal-selector="${initialItem}"]`;
  const initialItemExists = await page.$(initialItemSelector) !== null;

  // Loop through each entry in the multi-value field.
  for (const [index, multiItem] of Object.entries(multiValueField.items)) {
    if (index === '0' && !initialItemExists) {
      await clickButton(page, multiValueFieldButtonSelector);
    }
    if (index !== '0') {
      await clickButton(page, multiValueFieldButtonSelector);
    }

    // Make sure we have an item to fill, whether an "Add more" button was clicked or not.
    const resultItemKey = replacePlaceholder(index.toString(), '[INDEX]', multiValueFieldButtonSelector.resultValue);
    const resultItemSelector = `[data-drupal-selector="${resultItemKey}"]`;
    await page.waitForSelector(resultItemSelector);

    // Loop through each field item in each multi-value field entry.
    for (const fieldItem of multiItem) {

      // Update selectors for each field to match the current index.
      if (fieldItem.selector) {
        fieldItem.selector.value = replacePlaceholder(
          index.toString(),
          "[INDEX]",
          fieldItem.selector?.value ?? ''
        );
        fieldItem.selector.resultValue = replacePlaceholder(
          index.toString(),
          "[INDEX]",
          fieldItem.selector?.resultValue ?? ''
        );
      }

      // Fill form field normally with replaced indexes.
      await fillFormField(page, fieldItem, itemKey);
    }
  }
}

/**
 * Fill input field.
 *
 * Available selectors are
 * - data-drupal-selector
 * - role-label
 * - role
 * - label
 * - text
 *
 * Please see documentation for addtional info:
 * @see https://helsinkisolutionoffice.atlassian.net/wiki/spaces/KAN/pages/8481833123/Regressiotestit+WIP#fillInputField
 *
 * @param value
 *  Value inserted to a given field
 * @param selector
 *  Selector object. See test_data.ts for details.
 * @param page
 *  Page object from Playwright.
 * @param itemKey
 *  Item key used in data definition.
 */
async function fillInputField(value: string, selector: Selector | undefined, page: Page, itemKey: string) {
  const stringValue = value.toString();

  // If selector is not present, we cannot use this function.
  if (!selector) {
    return;
  }

  /**
   * Fills fields with given types/roles.
   */
  switch (selector.type) {
    case "data-drupal-selector":
      const customSelector = `[data-drupal-selector="${selector.value}"]`;
      // Fill field with selector
      await page.locator(customSelector).fill(value);

      // For some fields, playwright does not allow usage of data-drupal-selector
      // but code below works even worse. Probably because it's missing some
      // event triggering.

      // If above causes issues, we may need to add support for
      // page.$eval solution.

      // Use page.$eval to set the value of input elements
      // await page.$eval(customSelector, (element, value) => {
      //   (element as HTMLInputElement).value = value ?? '';
      // }, value);

      break;


    case "data-drupal-selector-sequential":
      const customSequentialSelector = `[data-drupal-selector="${selector.value}"]`;
      await page.locator(customSequentialSelector).pressSequentially(value);
      break;

    /**
     * Fill element with role & label selector.
     */
    case 'role-label':
      if (selector.details && selector.details.role && selector.details.label) {

        // @ts-ignore
        await page.getByRole(selector.details.role, selector.details.options)
          .getByLabel(selector.details.label).fill(stringValue);

      }
      break;

    /**
     * Fill field with role only selector.
     */
    case 'role':
      if (selector.details && selector.details.role && selector.details.options) {

        // @ts-ignore
        await page.getByRole(selector.details.role,
          selector.details.options).fill(stringValue);
      } else {
        logger(`Input: Role - incorrect settings -> ${itemKey}`);
      }
      break;

    /**
     * Fill field with Label selector. This isn't very good way to do this,
     * because labels can change and are subjective to used language.
     */
    case 'label':
      if (selector.details && selector.details.label) {
        await page.getByLabel(selector.details.label).fill(stringValue);

      }
      break;
    case 'text':
      if (selector.details && selector.details.text) {
        await page.getByText(selector.details.text, selector.details.options).fill(stringValue);
      }

      break;
  }
}

/**
 * Fills select fields.
 *
 * @param selector
 * @param page
 * @param value
 */
async function fillSelectField(selector: Selector | Partial<FormFieldWithRemove> | undefined, page: Page, value: string | undefined) {

  if (!selector) {
    return;
  }

  switch (selector.type) {

    case 'dom-id-first':
      if (typeof selector.value === 'string') {
        await page.locator(selector.value).selectOption({index: 1});
      }
      break;
    case 'by-label':
      if (selector.value && value) {
        const customSelector = `[data-drupal-selector="${selector.value}"]`;
        await page.waitForSelector(customSelector);
        await page.locator(customSelector).selectOption({label: value});
      }
      break;
    case 'data-drupal-selector':
      const customSelector = `[${selector.type}="${selector.value}"]`;

      // Use page.$eval to set the value of input elements
      await page.$eval(customSelector, (element, value) => {
        // Cast the element to HTMLSelectElement
        const selectElement = element as HTMLSelectElement;

        let newValue = value ?? 'XX';

        if (value === 'use-random-value' || value == '') {
          // Fetch all options from the select element
          const options = Array.from(selectElement.options);

          // Check if there are any options
          if (options.length > 0) {
            // Randomly select an option
            const randomIndex = Math.floor(Math.random() * options.length);
            const selectedOption = options[randomIndex];
            newValue = selectedOption.value;
          }
        }

        // Set the selected value of the select element
        selectElement.value = newValue;

        // Trigger the 'change' event to simulate user interaction
        const event = new Event('change', {bubbles: true});
        selectElement.dispatchEvent(event);
      }, value);

      // TODO: see if waits can be removed
      await page.waitForTimeout(2000);

      break;

  }
}

/**
 * Fill a checkbox.
 *
 * @param selector
 * @param itemKey
 * @param page
 */
async function fillCheckboxField(selector: Selector | undefined, itemKey: string, page: Page) {

  if (!selector) {
    return;
  }

  switch (selector.type) {

    case 'data-drupal-selector':

      if (selector.name && selector.value) {
        const customSelector = `[${selector.type}="${selector.value}"]`;

        await page.locator(customSelector).click();
      } else {
        logger(`Checkbox -> settings missing -> ${itemKey}`);
      }

      break;

    case 'text':

      if (selector.details && selector.details.text) {
        await page.getByText(selector.details.text, selector.details.options).click();
      } else {
        logger(`Checkbox -> settings missing -> ${itemKey}`);
      }

      break;

    case 'label':
      if (selector.details && selector.details.label) {
        await page.getByLabel(selector.details.label).check();
      } else {
        logger(`Checkbox -> settings missing -> ${itemKey}`);
      }
      break;

  }
}

/**
 * Fill radio field.
 *
 * @param selector
 * @param itemKey
 * @param page
 */
async function fillRadioField(selector: Selector | undefined, itemKey: string, page: Page) {

  if (!selector) {
    return;
  }

  switch (selector.type) {

    case 'text':

      if (selector.details && selector.details.text) {
        await page.getByText(selector.details.text, selector.details.options).click();
      } else {
        logger(`Radio: Text -> settings missing -> ${itemKey}`);
      }

      break;

    case 'dom-id':
      const radioSelector = selector.value; // Change this to the actual selector of your radio button
      await page.waitForSelector(radioSelector ?? '');

      try {
        if (radioSelector != null) {
          await page.click(radioSelector);
        }
      } catch (error) {
        logger('Error during click:', error);
      }

      // Wait for the change event to be processed
      // @ts-ignore
      await page.waitForSelector(radioSelector);
      break;

    case 'dom-id-label':
      const labelSelector = `.option.hds-radio-button__label[for="${selector.value}"]`;
      // Wait for the label to exist
      await page.waitForSelector(labelSelector);

      // Click on the label element
      await page.click(labelSelector);
      break;

    case 'partial-for-attribute':
      const labelForSelector = `.option.hds-radio-button__label[for*="${selector.value}"]`;
      await page.waitForSelector(labelForSelector);
      try {
        await page.click(labelForSelector);
      } catch (error) {
        logger(`Error clicking label with partial 'for' attribute: ${selector.value}`, error);
      }
      break;

  }
}

/**
 * Fill form field.
 *
 * @param page
 * @param formField
 * @param itemKey
 */
const fillFormField = async (page: Page, formField: Partial<FormFieldWithRemove>, itemKey: string) => {
  const {selector, value, role} = formField;

  /**
   * Fill normal input field.
   */
  if (role === "input" && value) {
    await fillInputField(value, selector, page, itemKey);
  }

  /**
   * Upload file via method.
   */
  if (role === "fileupload") {
    if (!selector || !selector.value) {
      return;
    }
    await uploadFile(
      page,
      selector.value,
      selector.resultValue ?? '',
      value
    )
  }

  /**
   * Fill select field
   */
  if (role === "select") {
    await fillSelectField(selector, page, value);
  }

  /**
   * Radio
   */
  if (role === 'radio') {
    await fillRadioField(selector, itemKey, page);
  }

  /**
   * Checkbox
   */
  if (role === 'checkbox') {
    await fillCheckboxField(selector, itemKey, page);
  }

  /**
   * Multi-value field.
   */
  if (role === 'multivalue') {
    await fillMultiValueField(page, formField, itemKey);
  }

  /**
   * Dynamic multi-value field.
   */
  if (role === 'dynamicmultivalue') {
    await fillDynamicMultiValueField(page, formField, itemKey);
  }

};

/**
 * Helper function to replace string in given string.
 *
 * @param index
 * @param placeholder
 * @param value
 */
const replacePlaceholder = (index: string, placeholder: string, value: string | undefined) => {
  if (!value) {
    return;
  }
  return value.replace(placeholder, index);
}

/**
 * Click button on the page. Wait for target page to load before progressing.
 *
 * @param page
 * @param buttonSelector
 * @param formClass
 * @param nextSelector
 */
const clickButton = async (
  page: Page,
  buttonSelector: Selector,
  formClass?: string,
  nextSelector?: string) => {

  switch (buttonSelector.type) {
    case 'data-drupal-selector':
      const customSelector = `[${buttonSelector.name}="${buttonSelector.value}"]`;

      await page.click(customSelector);
      break;

    case 'add-more-button':
      await page.getByRole('button', {name: buttonSelector.value}).click();
      break;

    case 'form-topnavi-link':
      await page.click(`li[data-webform-page="${buttonSelector.value}"] .grants-stepper__step__circle_container`);
      break;

    case 'wizard-next':
      try {
        const continueButton = await page.getByRole('button', {name: buttonSelector.value});
        // Use Promise.all to wait for navigation and button click concurrently
        await Promise.all([
          page.waitForNavigation({
            timeout: 5000, // Specify your timeout value in milliseconds
            waitUntil: 'domcontentloaded', // Adjust the event to wait for as needed
          }),
          continueButton.click(),
        ]);

        const selector = `[data-webform-key="${nextSelector}"]`;
        // Add a wait for a specific element on the next page to appear
        await page.waitForSelector(selector);

      } catch (error) {
        logger('Error during wizard next click:', error);
      }
      break;
  }

  // Wait for the page after button click to load
  await page.waitForLoadState();

  // hide super annoying cookie slider as soon as the page is loaded.
  await hideSlidePopup(page);

};

/**
 * Helper function to generate selectors based on rules. Deprecated?
 *
 * @param type
 * @param selectorValue
 */
const buildSelector = (type: string, selectorValue: string) => {
  if (type === 'data-drupal-selector') {
    return `[${type}="${selectorValue}"]`;
  }

  if (type === 'locator') {
    return selectorValue;
  }

  // Return as is as default
  return selectorValue;
}

/**
 * Upload file.
 *
 * @param page
 * @param uploadSelector
 * @param fileLinkSelector
 * @param filePath
 */
const uploadFile = async (
  page: Page,
  uploadSelector: string,
  fileLinkSelector: string,
  filePath: string = PATH_TO_TEST_PDF
) => {

  // Get upload handle
  const fileInput = page.locator(uploadSelector);

  // Get uploaded file link
  const fileLink = page.locator(fileLinkSelector)

  const responsePromise = page.waitForResponse(r => r.request().method() === "POST", {timeout: 30 * 1000});

  // FIXME: Use locator actions and web assertions that wait automatically
  await page.waitForTimeout(2000);

  await expect(fileInput).toBeAttached();
  await fileInput.setInputFiles(filePath);

  await page.waitForTimeout(2000);

  await expect(fileInput, "File upload failed").toBeHidden();
  await responsePromise;
}

/**
 * Get application number from breadcrumb path on the page.
 *
 * @param page
 */
const getApplicationNumberFromBreadCrumb = async (page: Page) => {
  // Specify the selector for the breadcrumb links
  const breadcrumbSelector = '.breadcrumb__link';

  // Use page.$$ to get an array of all matching elements
  const breadcrumbLinks = await page.$$(breadcrumbSelector);

  // Get the text content of the last link
  return await breadcrumbLinks[breadcrumbLinks.length - 1].textContent();

}

/**
 * Hide super annoying cookie consent popup.
 *
 * @param page
 */
const hideSlidePopup = async (page: Page) => {
  // Check if the element with id 'sliding-popup' exists
  const slidingPopup = await page.$('#sliding-popup');

  if (slidingPopup) {
    // If the element exists, manipulate it
    await slidingPopup.evaluate((popup) => {
      // Set the 'display' property to 'none' to hide the element
      popup.style.display = 'none';
    });
  } else {
    logger("Element with id 'sliding-popup' not found.");
  }
}


/**
 * Create form data.
 *
 * usage:
 *
 * const specificFormData: FormData = createFormData({
 *   title: 'Custom Title',
 *   formPages: {
 *     '2_avustustiedot': {
 *       items: {
 *         '__remove__': ['acting_year'],
 *         subvention_amount: {
 *           value: '1000',
 *         },
 *         // ... other overrides for items on this page
 *       },
 *       expectedDestination: '/custom/destination',
 *     },
 *   },
 *   expectedDestination: '/custom/destination',
 * });
 *
 * @param baseFormData
 * @param overrides
 */
function createFormData(baseFormData: FormData, overrides: Partial<FormData>): FormData {
  const formPages = Object.keys(baseFormData.formPages).reduce((result, pageKey) => {
    // @ts-ignore
    result[pageKey] = {
      ...baseFormData.formPages[pageKey],
      ...(overrides.formPages && overrides.formPages[pageKey]),
      items: {
        ...baseFormData.formPages[pageKey].items,
        ...(overrides.formPages &&
          overrides.formPages[pageKey] &&
          overrides.formPages[pageKey].items),
      },
    };

    if (overrides.formPages && overrides.formPages[pageKey] && overrides.formPages[pageKey].itemsToRemove) {
      // Remove items specified in overrides based on the itemsToRemove list
      // @ts-ignore
      overrides.formPages[pageKey].itemsToRemove.forEach((itemToRemove: string | number) => {
        // @ts-ignore
        delete result[pageKey]?.items[itemToRemove as string];
      });
    }

    return result;
  }, {});

  return {
    ...baseFormData,
    ...overrides,
    formPages,
  };
}

/**
 * Fill Hakijan Tiedot page for registered community.
 *
 * This form page is always the same within applicant type, so we can use
 * function to do the filling.
 *
 * @param formItems
 * @param page
 */
async function fillHakijanTiedotRegisteredCommunity(formItems: any, page: Page) {
  if (formItems['edit-email']) {
    await page.getByRole('textbox', {name: 'Sähköpostiosoite'}).fill(formItems['edit-email'].value);
  }
  if (formItems['edit-contact-person']) {
    await page.getByLabel('Yhteyshenkilö').fill(formItems['edit-contact-person'].value);
  }
  if (formItems['edit-contact-person-phone-number']) {
    await page.getByLabel('Puhelinnumero').fill(formItems['edit-contact-person-phone-number'].value);
  }

  if (formItems['edit-community-address-community-address-select']) {
    await page.locator('#edit-community-address-community-address-select').selectOption({ label: formItems['edit-community-address-community-address-select'].value});
    // await fillSelectField(
    //   formItems['edit-community-address-community-address-select'].selector ?? {
    //     type: 'dom-id-first',
    //     name: 'bank-account-selector',
    //     value: '#edit-bank-account-account-number-select',
    //   },
    //   page,
    //   undefined);
  }

  if (formItems['edit-bank-account-account-number-select']) {
    await page.locator('#edit-bank-account-account-number-select').selectOption({ label: formItems['edit-bank-account-account-number-select'].value });
    // await fillSelectField(
    //   formItems['edit-bank-account-account-number-select'].selector ?? {
    //     type: 'data-drupal-selector',
    //     name: 'bank-account-selector',
    //     value: 'edit-bank-account-account-number-select'
    //   },
    //   page,
    //   'use-random-value'
    // );
  }

  if (formItems['edit-community-officials-items-0-item-community-officials-select']) {
    const partialCommunityOfficialLabel = formItems['edit-community-officials-items-0-item-community-officials-select'].value;
    const optionToSelect = await page.locator('option', { hasText: partialCommunityOfficialLabel }).textContent() || '';
    await page.locator('#edit-community-officials-items-0-item-community-officials-select').selectOption({ label: optionToSelect });
    // await fillSelectField(
    //   formItems['edit-community-officials-items-0-item-community-officials-select'].selector ?? {
    //     type: 'data-drupal-selector',
    //     name: 'community-officials-selector',
    //     value: 'edit-community-officials-items-0-item-community-officials-select'
    //   },
    //   page,
    //   'use-random-value');
  }
}

/**
 * Fill Hakijan Tiedot page for private person.
 *
 * This form page is always the same within applicant type, so we can use
 * function to do the filling.
 *
 * @param formItems
 * @param page
 */
async function fillHakijanTiedotPrivatePerson(formItems: any, page: Page) {
  if (formItems['edit-bank-account-account-number-select']) {
    await page.locator('#edit-bank-account-account-number-select').selectOption({ label: formItems['edit-bank-account-account-number-select'].value });
    // await fillSelectField(
    //   formItems['edit-bank-account-account-number-select'].selector ?? {
    //     type: 'data-drupal-selector',
    //     name: 'bank-account-selector',
    //     value: 'edit-bank-account-account-number-select'
    //   },
    //   page,
    //   'use-random-value'
    // );
  }
}

/**
 * Fill Hakijan Tiedot page for unregistered community.
 *
 * This form page is always the same within applicant type, so we can use
 * function to do the filling.
 *
 * @param formItems
 * @param page
 */
async function fillHakijanTiedotUnregisteredCommunity(formItems: any, page: Page) {

  if (formItems['edit-bank-account-account-number-select']) {
    await page.locator('#edit-bank-account-account-number-select').selectOption({ label: formItems['edit-bank-account-account-number-select'].value });
    // await fillSelectField(
    //   formItems['edit-bank-account-account-number-select'].selector ?? {
    //     type: 'data-drupal-selector',
    //     name: 'bank-account-selector',
    //     value: 'edit-bank-account-account-number-select'
    //   },
    //   page,
    //   'use-random-value'
    // );
  }
  if (formItems['edit-community-officials-items-0-item-community-officials-select']) {
    const partialCommunityOfficialLabel = formItems['edit-community-officials-items-0-item-community-officials-select'].value;
    const optionToSelect = await page.locator('option', { hasText: partialCommunityOfficialLabel }).textContent() || '';
    await page.locator('#edit-community-officials-items-0-item-community-officials-select').selectOption({ label: optionToSelect });
    // await fillSelectField(
    //   formItems['edit-community-officials-items-0-item-community-officials-select'].selector ?? {
    //     type: 'data-drupal-selector',
    //     name: 'community-officials-selector',
    //     value: 'edit-community-officials-items-0-item-community-officials-select'
    //   },
    //   page,
    //   'use-random-value');
  }
  // await page.pause();
}

const fillSelectIfElementExists = async (
  selector: Partial<FormFieldWithRemove> | undefined,
  page: Page,
  value: string
) => {


  if (selector) {
    await fillSelectField(selector, page, value);
  }


};

export {
  fillProfileForm,
  fillFormField,
  clickButton,
  uploadFile,
  createFormData,
  hideSlidePopup,
  fillGrantsFormPage,
  fillSelectField,
  fillInputField,
  fillCheckboxField,
  fillHakijanTiedotRegisteredCommunity,
  fillSelectIfElementExists,
  fillHakijanTiedotPrivatePerson,
  fillHakijanTiedotUnregisteredCommunity
};


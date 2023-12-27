import {Locator, Page, expect} from "@playwright/test";
import {
  FormField,
  MultiValueField,
  FormData,
  Selector,
  isMultiValueField,
  DynamicMultiValueField,
  isDynamicMultiValueField, PageHandlers,
  FormFieldWithRemove
} from "./data/test_data"

import {
  PATH_TO_TEST_PDF,
  slowLocator,
  saveObjectToEnv,
  extractUrl
} from "./helpers";


/**
 *
 * @param page
 * @param formClass
 */
async function setNoValidate(page: Page, formClass: string) {
  await page.waitForSelector(`.${formClass}`);
  console.log('Set NOVALIDATE called');

  const formHandle = await page.$(`.${formClass}`);

  if (formHandle) {
    const isFormPresent = await formHandle.evaluate((form) => {
      console.log('Is Form Present:', form !== null);
      return form !== null;
    });

    if (isFormPresent) {
      console.log('Set NOVALIDATE inside formHandle');
      try {
        const result = await formHandle.evaluate((form) => {
          console.log('Set NOVALIDATE inside EVALUATE');
          form.setAttribute('novalidate', '');
          return 'Evaluation successful';
        });

        console.log('Evaluation Result:', result);
      } catch (error) {
        console.error('Error inside evaluate:', error);
      }
    } else {
      console.error('Form not found in the DOM');
    }
  }
}


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
  console.log('FORM', formPath, formClass);

  // Assertions based on the expected destination
  const initialPathname = new URL(page.url()).pathname;
  const expectedPattern = new RegExp(`^${formDetails.expectedDestination}`);
  expect(initialPathname).toMatch(expectedPattern);

  // page.locator = slowLocator(page, 10000);

  const applicationId = await getApplicationNumberFromBreadCrumb(page);
  const submissionUrl = await extractUrl(page);

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

    console.log('Form page:', formPageKey);

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


    if (pageHandlers[formPageKey]) {
      await pageHandlers[formPageKey](page, formPageObject);
    } else {
      continue;
    }

    if (buttons.length > 0) {
      const firstButton = buttons[0];
      if (firstButton.selector) {
        await clickButton(page, firstButton.selector, formClass, formPageKey);

        // Here we already are on the new page that is loaded via clickButton

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
        console.log('ERRORS', actualErrorMessages);
      }
      // Expect actual error messages size to be 0
      expect(actualErrorMessages.length).toBe(0);
    }

    // Check for expected error messages
    for (const [selector, expectedErrorMessage] of expectedErrors) {
      if (expectedErrorMessage) {
        console.log('ERROR', expectedErrorMessage);
        console.log('ERRORS', actualErrorMessages);
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

  // await page.getByRole('link', {name: 'Katsele hakemusta'}).click();
  //
  // await expect(page.getByRole('heading', {name: 'Hakemuksen tiedot'})).toBeVisible();
  // await expect(page.getByRole('link', {name: 'Tulosta hakemus'})).toBeVisible();
  // await expect(page.getByRole('link', {name: 'Kopioi hakemus'})).toBeVisible();

  // const applicationData = await page.locator(".webform-submission").innerText()
  // Object.values(userInputData).forEach(value => expect(applicationData).toContain(value))

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
        console.error('Error while fetching text content:', error);
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
  let foundExpectedError = false;
  for (const [selector, expectedErrorMessage] of expectedErrors) {
    if (expectedErrorMessage) {
      // If an error is expected, check if it's present in the captured error messages
      if (actualErrorMessages.some((msg) => msg.includes(<string>expectedErrorMessage))) {
        foundExpectedError = true;
        break; // Break the loop if any expected error is found
      }
    } else {
      // If no error is expected, check if there are no error messages
      expect(allErrorElements.length).toBe(0);
    }
  }

  // If you expect at least one error but didn't find any
  if (expectedErrors.length > 0 && !foundExpectedError) {
    // console.error('ERROR: Expected error not found');
    // console.debug('ACTUAL ERRORS', actualErrorMessages);
    expect('Errors expected, but got none').toBe('');
  }

  // Check for unexpected error messages
  const unexpectedErrors = actualErrorMessages.filter(msg => !expectedErrorsArray.includes(msg));
  if (unexpectedErrors.length !== 0) {
    console.log('unexpectedErrors', unexpectedErrors);
  }
  // If any unexpected errors are found, the test fails
  expect(unexpectedErrors.length === 0).toBe(true);
}

/**
 * Fill multivalue field with radio buttons to signal visibility.
 *
 * @param page
 * @param dynamicMultiValueField
 * @param itemKey
 */
const fillDynamicMultiValueField = async (page: Page, dynamicMultiValueField: Partial<FormFieldWithRemove>, itemKey: string) => {

  // We really need this element
  if (!dynamicMultiValueField.dynamic_multi) {
    return;
  }

  // Get radio via label & click it
  const labelSelector = `.option.hds-radio-button__label[for="${dynamicMultiValueField.dynamic_multi.radioSelector.value}"]`;
  await page.waitForSelector(labelSelector);
  await page.click(labelSelector);

  if (dynamicMultiValueField.dynamic_multi.revealedElementSelector.value) {
    // Wait for the dynamically revealed elements to appear
    const revealedElementSelector = dynamicMultiValueField.dynamic_multi.revealedElementSelector.value;
    await page.waitForSelector(revealedElementSelector);
  }

  const dynamicField = dynamicMultiValueField.dynamic_multi.multi_field;

  if (!dynamicField.buttonSelector.resultValue) {
    return;
  }
  // See if we have initial element
  const replacedFirstItem = replacePlaceholder('0', '[INDEX]', dynamicField.buttonSelector.resultValue);
  const firstItemSelector = `[data-drupal-selector="${replacedFirstItem}"]`;

  const firstElementExists = await page.$(firstItemSelector) !== null;

  for (const [index, multiItem] of Object.entries(dynamicField.items)) {
    if (index === '0' && !firstElementExists) {
      await clickButton(page, dynamicField.buttonSelector);
    }
    if (index !== '0' && firstElementExists) {
      await clickButton(page, dynamicField.buttonSelector);
    }
    // Replace placeholder with actual index
    const resultItemKey = replacePlaceholder(index.toString(), '[INDEX]', dynamicField.buttonSelector.resultValue);
    // Make sure we have the result item ready
    const resultItemSelector = `[data-drupal-selector="${resultItemKey}"]`;
    await page.waitForSelector(resultItemSelector);

    // Then loop elements fields normally
    for (const fieldItem of multiItem) {
      // Parse result selector. This is the element that gets added via ajax.
      const resultSelector = replacePlaceholder(index.toString(), "[INDEX]", dynamicField.buttonSelector.resultValue ?? '');
      // Maybe redundant, but seems that even here sometimes the element is not present yet.
      const resultSelectorFull = `[${dynamicField.buttonSelector.name}="${resultSelector}"]`;
      await page.waitForSelector(resultSelectorFull, {
        state: 'visible',
        timeout: 5000
      });

      // Update selectors for multivaluefield
      const replacedFieldItem = {
        ...fieldItem
      };
      replacedFieldItem.selector = {
        type: fieldItem.selector.type,
        value: replacePlaceholder(index.toString(), "[INDEX]", fieldItem.selector.value ?? ''),
        // TODO: Remove this only usage of selector.name
        name: fieldItem.selector.name,
        resultValue: replacePlaceholder(index.toString(), "[INDEX]", fieldItem.selector.resultValue ?? ''),
      };
      // Fill form field normally with replaced indexes.
      await fillFormField(page, replacedFieldItem, itemKey);

    }
  }
}

/**
 * Fill multivalued field using fillFormField function to do it.
 *
 * @param page
 * @param multiValueField
 * @param itemKey
 */
const fillMultiValueField = async (page: Page, multiValueField: Partial<FormFieldWithRemove>, itemKey: string) => {
  if (multiValueField.dynamic_multi && isDynamicMultiValueField(multiValueField.dynamic_multi)) {

    const dynamicField = multiValueField.dynamic_multi.multi_field;

    // tsekataan onko ekaa multielementtiä lisätty automaagisesti
    const replacedFirstItem = replacePlaceholder(dynamicField.buttonSelector.resultValue ?? '', '[INDEX]', '0');
    const firstItemSelector = `[data-drupal-selector="${replacedFirstItem}"]`;

    // Check if an element with the specified data-selector exists

    const firstElementExists = await page.$(firstItemSelector) !== null;

    // console.log('1st item selector', firstItemSelector, firstElementExists);

    // await page.pause();

    for (const [index, multiItem] of Object.entries(dynamicField.items)) {
      if (index === '0' && !firstElementExists) {
        await clickButton(page, dynamicField.buttonSelector);
      }
      if (index !== '0' && firstElementExists) {
        await clickButton(page, dynamicField.buttonSelector);
      }

      for (const fieldItem of multiItem) {
        // Parse result selector. This is the element that gets added via ajax.
        const resultSelector = replacePlaceholder(index.toString(), "[INDEX]", dynamicField.buttonSelector.resultValue ?? '');
        const resultSelectorFull = `[${dynamicField.buttonSelector.name}="${resultSelector}"]`;
        // wait for the element to appear.
        await page.waitForSelector(resultSelectorFull, {
          state: 'visible',
          timeout: 5000
        });

        const replacedFieldItem = {
          ...fieldItem
        };

        replacedFieldItem.selector = {
          type: fieldItem.selector.type,
          value: replacePlaceholder(index.toString(), "[INDEX]", fieldItem.selector.value ?? ''),
          // TODO: Remove this only usage of selector.name
          name: fieldItem.selector.name,
          resultValue: replacePlaceholder(index.toString(), "[INDEX]", fieldItem.selector.resultValue ?? ''),
        };

        await fillFormField(page, replacedFieldItem, itemKey);

      }

    }

  }
  if (multiValueField.multi && isMultiValueField(multiValueField.multi)) {
    const buttonSelector = `[${multiValueField.multi?.buttonSelector.type}="${multiValueField.multi?.buttonSelector.value}"]`;

    // @ts-ignore
    for (const [index, multiItem] of Object.entries(multiValueField.multi.items)) {
      // Click button to add new element
      await clickButton(page, multiValueField.multi?.buttonSelector)

      for (const fieldItem of multiItem) {
        // Parse result selector. This is the element that gets added via ajax.
        const resultSelector = replacePlaceholder(index.toString(), "[INDEX]", multiValueField.multi.buttonSelector.resultValue ?? '');
        const resultSelectorFull = `[${multiValueField.multi.buttonSelector.name}="${resultSelector}"]`;

        // wait for the element to appear.
        await page.waitForSelector(resultSelectorFull, {
          state: 'visible',
          timeout: 5000
        })

        const replacedFieldItem = {
          ...fieldItem
        };

        replacedFieldItem.selector = {
          type: fieldItem.selector.type,
          value: replacePlaceholder(
            index.toString(),
            "[INDEX]",
            fieldItem.selector.value
          ),
          // TODO: Remove this only usage of selector.name
          name: fieldItem.selector.name,
          resultValue: replacePlaceholder(index.toString(), "[INDEX]", fieldItem.selector.resultValue ?? ''),
        };

        await fillFormField(page, replacedFieldItem, itemKey);

      }
    }
  }
}

/**
 * Fill input field.
 *
 * @param value
 * @param selector
 * @param page
 * @param itemKey
 */
async function fillInputField(value: string, selector: Selector | undefined, page: Page, itemKey: string) {
  const stringValue = value.toString();

  if (!selector) {
    return;
  }

  switch (selector.type) {
    case "data-drupal-selector":
      const customSelector = `[data-drupal-selector="${selector.value}"]`;

      // console.log('Input DDS:', customSelector, value);

      // Use page.$eval to set the value of input elements
      await page.$eval(customSelector, (element, value) => {
        (element as HTMLInputElement).value = value ?? '';
      }, value);

      break;

    case 'role-label':
      if (selector.details && selector.details.role && selector.details.label) {
        // console.log(`Input: Role (${selector.details.role}) & Label (${selector.details.label}), value: ${stringValue}`);

        // @ts-ignore
        await page.getByRole(selector.details.role, selector.details.options).getByLabel(selector.details.label).fill(stringValue);

      }

      break;
    case 'role':

      if (selector.details && selector.details.role && selector.details.options) {
        // console.log(`Input: Role (${selector.details.role}, ${selector.details.options.name}) -> ${itemKey} -> value: ${stringValue}`);

        // @ts-ignore
        await page.getByRole(selector.details.role,
          selector.details.options).fill(stringValue);
      } else {
        console.log(`Input: Role - incorrect settings -> ${itemKey}`);
      }

      break;
    case 'label':

      if (selector.details && selector.details.label) {
        // console.log(`Input: Label (${selector.details.label}), value: ${stringValue}`);

        // @ts-ignore
        await page.getByLabel(selector.details.label).fill(stringValue);

      }

      break;
    case 'text':

      if (selector.details && selector.details.text) {
        // console.log(`Normal item: Text (${selector.details.label}), value: ${stringValue}`);
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
async function fillSelectField(selector: Selector | undefined, page: Page, value: string | undefined) {

  if (!selector) {
    return;
  }

  switch (selector.type) {

    case 'dom-id-first':
      if (typeof selector.value === 'string') {
        await page.locator(selector.value).selectOption({index: 1});
      }
      break;
    case 'data-drupal-selector':
      const customSelector = `[${selector.type}="${selector.value}"]`;

      // Use page.$eval to set the value of input elements
      await page.$eval(customSelector, (element, value) => {
        // Cast the element to HTMLSelectElement
        const selectElement = element as HTMLSelectElement;

        let newValue = value ?? 'XX';

        if (value === 'use-random-value') {
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

      await page.waitForTimeout(2000);
      // await page.pause();

      break;

  }
}

async function fillCheckboxField(selector: Selector | undefined, itemKey: string, page: Page) {

  if (!selector) {
    return;
  }

  switch (selector.type) {

    case 'data-drupal-selector':

      if (selector.name && selector.value) {
        // console.log(`Checkbox: -> ${itemKey}`);
        const customSelector = `[${selector.type}="${selector.value}"]`;

        await page.locator(customSelector).click();
      } else {
        console.log(`Checkbox -> settings missing -> ${itemKey}`);
      }

      break;

    case 'text':

      if (selector.details && selector.details.text) {
        // console.log(`Checkbox: -> ${itemKey}`);
        await page.getByText(selector.details.text, selector.details.options).click();
      } else {
        console.log(`Checkbox -> settings missing -> ${itemKey}`);
      }

      break;

    case 'label':
      if (selector.details && selector.details.label) {
        // console.log(`Checkbox -> ${itemKey}`);
        await page.getByLabel(selector.details.label).check();
      } else {
        console.log(`Checkbox -> settings missing -> ${itemKey}`);
      }
      break;

  }
}

async function fillRadioField(selector: Selector | undefined, itemKey: string, page: Page) {

  if(!selector) {
    return;
  }

  switch (selector.type) {

    case 'text':

      if (selector.details && selector.details.text) {
        // console.log(`Radio: Text -> ${itemKey}`);
        await page.getByText(selector.details.text, selector.details.options).click();
      } else {
        console.log(`Radio: Text -> settings missing -> ${itemKey}`);
      }

      break;

    case 'dom-id':
      // console.log('Radio: id=', selector.value);
      const radioSelector = selector.value; // Change this to the actual selector of your radio button

      // Wait for the radio button to exist
      // @ts-ignore
      await page.waitForSelector(radioSelector);

      try {
        // Click on the radio button
        if (radioSelector != null) {
          await page.click(radioSelector);
        }
      } catch (error) {
        console.error('Error during click:', error);
      }

      // Wait for the change event to be processed
      // @ts-ignore
      await page.waitForSelector(radioSelector);
      break;
    case 'dom-id-label':
      const labelSelector = `.option.hds-radio-button__label[for="${selector.value}"]`;
      // Wait for the label to exist

      // console.log('Radio: id=', labelSelector);

      await page.waitForSelector(labelSelector);

      // Click on the label element
      await page.click(labelSelector);
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

  // page.on('console', (message) => {
  //   console.log(`Browser Console ${message.type()}: ${message.text()}`);
  // });


  if (role === "input" && value) {
    // console.log(selector, value);
    await fillInputField(value, selector, page, itemKey);
    // await page.pause();
  }

  if (role === "fileupload") {

    if (!selector || !selector.value) {
      return;
    }

    // console.log('Fileupload:', selector.value, value);

    await uploadFile(
      page,
      selector.value,
      selector.resultValue ?? '',
      value
    )
  }

  if (role === "select") {
    await fillSelectField(selector, page, value);
  }

  if (role === 'radio') {
    await fillRadioField(selector, itemKey, page);
  }

  if (role === 'checkbox') {
    await fillCheckboxField(selector, itemKey, page);
  }

};

const replacePlaceholder = (index: string, placeholder: string, value: string | undefined) => {
  if (!value) {
    return;
  }
  return value.replace(placeholder, index);
}

/**
 * Click button on the page.
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

      // console.log('Button selector', customSelector);
      await page.click(customSelector);
      break;

    case 'add-more-button':
      // console.log('Add more button clicked');
      await page.getByRole('button', {name: buttonSelector.value}).click();
      break;

    case 'form-topnavi-link':
      // console.log('Topnavi link clicked');

      await page.click(`li[data-webform-page="${buttonSelector.value}"] .grants-stepper__step__circle_container`);
      break;

    case 'wizard-next':
      try {
        // console.log('Wizard next clicked');
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
        console.error('Error during wizard next click:', error);
      }
      break;
  }

  // Wait for the page after button click to load
  await page.waitForLoadState();
  // console.log('...ready');

  // hide super annoying cookie slider as soon as the page is loaded.
  await hideSlidePopup(page);

};

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


const getApplicationNumberFromBreadCrumb = async (page: Page) => {
  // Specify the selector for the breadcrumb links
  const breadcrumbSelector = '.breadcrumb__link';

  // Use page.$$ to get an array of all matching elements
  const breadcrumbLinks = await page.$$(breadcrumbSelector);

  // Get the text content of the last link
  return await breadcrumbLinks[breadcrumbLinks.length - 1].textContent();

}

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
    console.log("Element with id 'sliding-popup' not found.");
  }
}

const waitForSelector = async (page: Page, selector: string) => {
  await page.waitForSelector(selector, {state: 'attached'});
};

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
  fillCheckboxField
};


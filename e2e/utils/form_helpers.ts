import {Locator, Page, expect} from "@playwright/test";
import {
  FormField,
  MultiValueField,
  FormData,
  Selector
} from "./data/test_data"

const fillForm = async (
  page: Page,
  formDetails: FormData,
  formPath: string,
  formClass: string,
) => {

  // Navigate to form url.
  await page.goto(formPath);

  // Assertions based on the expected destination
  const initialPathname = new URL(page.url()).pathname;
  // expect(actualPathname).toMatch(new RegExp(`^${formDetails.expectedDestination}/?$`));
  expect(initialPathname).toEqual(formPath);

  if (formClass) {
    /**
     * Browser validation breaks up our tests regarding error validation.
     * So here we disable browser validation by setting novalidate for form.
     */
    await page.evaluate((classSelector) => {
      // Get form
      const form = document.querySelector(`.${classSelector}`);
      if (form) {
        // Set novalidate attribute
        form.setAttribute('novalidate', '');
      }
    }, formClass);
  }

  // Loop form pages
  for (const formPage of formDetails.formPages) {
    const buttons = [];
    for (const field of formPage) {
      if (field.role === 'button') {
        // Collect buttons to be clicked later
        buttons.push(field);
      } else if (field.role === 'multivalue') {
        // Process multivalue fields separately
        await fillMultiValueField(page, field);
      } else {
        // Or fill simple form field
        await fillFormField(page, field);
      }
    }

    // Click buttons after filling in the fields
    for (const button of buttons) {
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
      // Expect actual error messages size to be 0
      expect(actualErrorMessages.length).toBe(0);
      // print errors to stdout
      if (actualErrorMessages.length !== 0) {
        console.log('ERRORS', actualErrorMessages);
      }
    }

    // Check for expected error messages
    for (const [selector, expectedErrorMessage] of expectedErrors) {
      if (expectedErrorMessage) {
        console.log('ERROR', expectedErrorMessage);
        console.log('ERRORS', actualErrorMessages);
        // If an error is expected, check if it's present in the captured error messages
        expect(actualErrorMessages.some((msg) => msg.includes(expectedErrorMessage))).toBe(true);
      } else {
        // If no error is expected, check if there are no error messages
        expect(allErrorElements.length).toBe(0);
      }
    }

    // Assertions based on the expected destination
    const actualPathname = new URL(page.url()).pathname;
    // expect(actualPathname).toMatch(new RegExp(`^${formDetails.expectedDestination}/?$`));
    expect(actualPathname).toEqual(formDetails.expectedDestination);
  }
};

import {PATH_TO_TEST_PDF} from "./helpers";

/**
 * Fill multivalued field using fillFormField function to do it.
 *
 * @param page
 * @param multiValueField
 */
const fillMultiValueField = async (page: Page, multiValueField: FormField) => {
  if (multiValueField.multi) {
    const buttonSelector = `[${multiValueField.multi?.buttonSelector.type}="${multiValueField.multi?.buttonSelector.value}"]`;

    // @ts-ignore
    for (const [index, multiItem] of Object.entries(multiValueField.multi.items)) {
      // Click button to add new element
      await clickButton(page, multiValueField.multi?.buttonSelector)

      for (const fieldItem of multiItem) {
        // Parse result selector. This is the element that gets added via ajax.
        const resultSelector = replacePlaceholder(
          index.toString(),
          "[INDEX]",
          multiValueField.multi.buttonSelector.resultValue ?? ''
        );
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
          name: fieldItem.selector.name,
          resultValue: replacePlaceholder(
            index.toString(),
            "[INDEX]",
            fieldItem.selector.resultValue ?? ''
          ),
        };

        await fillFormField(page, replacedFieldItem);

      }
    }
  }
}

const fillFormField = async (page: Page, formField: FormField) => {
  const {selector, value, role} = formField;

  page.on('console', (message) => {
    console.log(`Browser Console ${message.type()}: ${message.text()}`);
  });

  if (role === "input" && selector.type === 'data-drupal-selector') {
    const customSelector = `[data-drupal-selector="${selector.value}"]`;

    console.log('Normal item:', customSelector, value);

    // Use page.$eval to set the value of input elements
    await page.$eval(customSelector, (element, value) => {
      (element as HTMLInputElement).value = value ?? '';
      // console.log('EVAL sisällä', value);
    }, value);
  }

  if (role === "fileupload") {

    console.log('Fileupload:', selector.value, value);

    await uploadFile(
      page,
      selector.value,
      selector.resultValue ?? '',
      value
    )
  }

  if (role === "select") {

    const customSelector = `[${selector.type}="${selector.value}"]`;

    // Use page.$eval to set the value of input elements
    await page.$eval(customSelector, (element, value) => {
      // Cast the element to HTMLSelectElement
      const selectElement = element as HTMLSelectElement;

      // Set the selected value of the select element
      selectElement.value = value ?? '';

      // Trigger the 'change' event to simulate user interaction
      const event = new Event('change', {bubbles: true});
      selectElement.dispatchEvent(event);
    }, value);


  }

};

const replacePlaceholder = (index: string, placeholder: string, value: string) => {
  return value.replace(placeholder, index);
}

const clickButton = async (page: Page, buttonSelector: Selector) => {

  // console.log('Button clicked', buttonSelector);

  if (buttonSelector.type === 'data-drupal-selector') {
    const customSelector = `[${buttonSelector.name}="${buttonSelector.value}"]`;

    // Use page.click to interact with buttons

    console.log('Button selector', customSelector);
    await page.click(customSelector);
    console.log('...ready');
  }

  if (buttonSelector.type === 'add-more-button') {
    console.log('Add more button clicked');
    await page.getByRole('button', {name: buttonSelector.value}).click();
    console.log('...ready');
  }


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


export {
  fillForm,
  fillFormField,
  clickButton,
  uploadFile
};


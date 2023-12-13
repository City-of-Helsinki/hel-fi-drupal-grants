import {Locator, Page, expect} from "@playwright/test";


interface FormField {
  role: string;
  selectorType: string;
  selector: string;
  value?: string;
}

interface FormData {
  formPages: Array<Array<FormField>>,
  expectedDestination: string,
  expectedErrors: Object
}

const fillForm = async (page: Page, formDetails: FormData) => {
  for (const formPage of formDetails.formPages) {
    const buttons = [];
    for (const field of formPage) {
      if (field.role === 'button') {
        buttons.push(field);  // Collect buttons to be clicked later
      } else {
        await fillFormField(page, field);
      }
    }

    // Click buttons after filling in the fields
    for (const button of buttons) {
      await clickButton(page, button);
    }

    // Assertions based on the expected destination
    const actualPathname = new URL(page.url()).pathname;
    expect(actualPathname).toMatch(new RegExp(`^${formDetails.expectedDestination}/?$`));

    // Capture all error messages on the page
    const allErrorElements = await page.$$('.form-item--error-message'); // Adjust selector based on your actual HTML structure
    const actualErrorMessages = await Promise.all(
      allErrorElements.map(async (element) => await element.innerText())
    );

    // Check for expected error messages
    for (const [selector, expectedErrorMessage] of Object.entries(formDetails.expectedErrors)) {
      if (expectedErrorMessage) {
        // If an error is expected, check if it's present in the captured error messages
        expect(actualErrorMessages.some((msg) => msg.includes(expectedErrorMessage))).toBe(true);
      } else {
        // If no error is expected, check if there are no error messages
        expect(allErrorElements.length).toBe(0);
      }
    }


  }
};


const fillFormField = async (page: Page, formField: FormField) => {
  const { selectorType, selector, value } = formField;

  if (selectorType === 'data-drupal-selector') {
    const customSelector = `[data-drupal-selector="${selector}"]`;

    // Use page.$eval to set the value of input elements
    await page.$eval(customSelector, (element, value) => {
      (element as HTMLInputElement).value = value ?? '';
    }, value);

  }
  // Add more conditions for other selector types if needed
};

const clickButton = async (page: Page, buttonDetails: FormField) => {
  const { selectorType, selector } = buttonDetails;

  if (selectorType === 'data-drupal-selector') {
    const customSelector = `[data-drupal-selector="${selector}"]`;

    // Use page.click to interact with buttons
    await page.click(customSelector);
  }
  // Add more conditions for other selector types if needed
};


export {
  fillForm,
  fillFormField,
  FormField,
  FormData,
  clickButton
};


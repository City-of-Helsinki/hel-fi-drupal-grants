import {Locator, Page, expect} from "@playwright/test";
import {
  FormField,
  MultiValueField,
  FormData,
  Selector, isMultiValueField, DynamicMultiValueField, isDynamicMultiValueField
} from "./data/test_data"

import {PATH_TO_TEST_PDF} from "./helpers";

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
  // expect(initialPathname).toEqual(formPath);

  // const formExists = await page.locator("body")
  //   .evaluate(el => el.classList.contains(formClass));
  //
  // expect(formExists).toBeTruthy();

  if (formClass) {
    /**
     * Browser validation breaks up our tests regarding error validation.
     * So here we disable browser validation by setting novalidate for form.
     */
    // await page.evaluate((classSelector) => {
    //   // Get form
    //   const form = document.querySelector(`.${classSelector}`);
    //   if (form) {
    //     // Set novalidate attribute
    //     form.setAttribute('novalidate', '');
    //   }
    // }, formClass);
    await setNoValidate(page, formClass);
  }

  // Loop form pages
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
        await fillMultiValueField(page, itemField);
      } else {
        // Or fill simple form field
        await fillFormField(page, itemField);
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

const fillGrantsForm = async (
  page: Page,
  formDetails: FormData,
  formPath: string,
  formClass: string,
) => {

  // Navigate to form url.
  await page.goto(formPath);
  console.log('FORM', formPath, formClass);

    // Loop form pages
  for (const [formPageKey, formPageObject]
    of Object.entries(formDetails.formPages)) {

    console.log('Form page:', formPageKey);

    const selector = `[data-webform-key="${formPageKey}"]`;
    await waitForSelector(page, selector);

    await setNoValidate(page, formClass);

    const buttons = [];

    // Loop through items on the current page
    for (const [itemKey, itemField]
      of Object.entries(formPageObject.items)) {

      if (itemField.role === 'button') {
        // Collect buttons to be clicked later
        buttons.push(itemField);
      } else if (itemField.role === 'dynamicmultifield') {
        // Process multivalue fields separately
        await fillDynamicMultiValueField(page, itemField);
      } else if (itemField.role === 'multivalue') {
        // Process multivalue fields separately
        await fillMultiValueField(page, itemField);
      } else {
        // Or fill simple form field
        await fillFormField(page, itemField);
      }
    } // end itemField for
  };

}


//
// const fillGrantsForm = async (
//   page: Page,
//   formDetails: FormData,
//   formPath: string,
//   formClass: string,
// ) => {
//
//   // Navigate to form url.
//   await page.goto(formPath);
//   console.log('FORM', formPath);
//
//
//   const lastLinkText = await saveApplicationNumberFromBreadCrumb(page);
//   console.log('Last link text:', lastLinkText);
//
//   // Assertions based on the expected destination
//   // const initialPathname = new URL(page.url()).pathname;
//   // expect(initialPathname).toMatch(new RegExp(`^${formDetails.expectedDestination}/?$`));
//   // expect(initialPathname).toEqual(formPath);
//
//   // const formExists = await page.locator("body")
//   //   .evaluate(el => el.classList.contains(formClass));
//   //
//   // expect(formExists).toBeTruthy();
//
//   await hideSlidePopup(page);
//
//   // Loop form pages
//   for (const [formPageKey, formPageObject]
//     of Object.entries(formDetails.formPages)) {
//
//     console.log('Form page:', formPageKey);
//
//     const selector = `[data-webform-key="${formPageKey}"]`;
//     await waitForSelector(page, selector);
//
//     await setNoValidate(page, formClass);
//
//     const buttons = [];
//
//     // Loop through items on the current page
//     for (const [itemKey, itemField]
//       of Object.entries(formPageObject.items)) {
//
//       if (itemField.role === 'button') {
//         // Collect buttons to be clicked later
//         buttons.push(itemField);
//       } else if (itemField.role === 'dynamicmultifield') {
//         // Process multivalue fields separately
//         await fillDynamicMultiValueField(page, itemField);
//       } else if (itemField.role === 'multivalue') {
//         // Process multivalue fields separately
//         await fillMultiValueField(page, itemField);
//       } else {
//         // Or fill simple form field
//         await fillFormField(page, itemField);
//       }
//     } // end itemField for
//
//     // Take screenshot after filling form, before submitting anything
//     const screenshotPath = `./test-results/${formDetails.formSelector}/${formPageKey}.png`;
//     const screenshotPathPostBtn = `./test-results/${formDetails.formSelector}/${formPageKey}_afterbtn.png`;
//     await page.screenshot({fullPage: true, path: screenshotPath});
//
//     // Click buttons after filling in the fields
//     for (const button of buttons) {
//       await clickButton(page, button.selector, formClass, formPageKey);
//     }
//
//     await page.waitForLoadState("load");
//
//     await hideSlidePopup(page);
//
//     await page.screenshot({fullPage: true, path: screenshotPathPostBtn});
//     //
//     // // Capture all error messages on the page
//     // const allErrorElements =
//     //   await page.$$('.hds-notification--error .hds-notification__body ul li');
//     //
//     // // Extract text content from the error elements
//     // const actualErrorMessages = await Promise.all(
//     //   allErrorElements.map(async (element) => {
//     //     try {
//     //       return await element.innerText();
//     //     } catch (error) {
//     //       console.error('Error while fetching text content:', error);
//     //       return '';
//     //     }
//     //   })
//     // );
//     //
//     // // Get configured expected errors from form PAGE
//     // const expectedErrors = Object.entries(formDetails.expectedErrors);
//     // const expectedErrorsArray = expectedErrors.map(([selector, expectedErrorMessage]) => expectedErrorMessage);
//     //
//     // // If we're not expecting errors...
//     // if (expectedErrors.length === 0) {
//     //   // Print errors to debug output
//     //   if (actualErrorMessages.length !== 0) {
//     //     console.debug('ERRORS, expected / actual', expectedErrors, actualErrorMessages);
//     //   }
//     //   // Expect actual error messages size to be 0
//     //   expect(actualErrorMessages.length).toBe(0);
//     // }
//     //
//     // // Check for expected error messages
//     // let foundExpectedError = false;
//     // for (const [selector, expectedErrorMessage] of expectedErrors) {
//     //   if (expectedErrorMessage) {
//     //     // If an error is expected, check if it's present in the captured error messages
//     //     if (actualErrorMessages.some((msg) => msg.includes(<string>expectedErrorMessage))) {
//     //       foundExpectedError = true;
//     //       break; // Break the loop if any expected error is found
//     //     }
//     //   } else {
//     //     // If no error is expected, check if there are no error messages
//     //     expect(allErrorElements.length).toBe(0);
//     //   }
//     // }
//     //
//     // // If you expect at least one error but didn't find any
//     // if (expectedErrors.length > 0 && !foundExpectedError) {
//     //   console.error('ERROR: Expected error not found');
//     //   console.debug('ACTUAL ERRORS', actualErrorMessages);
//     //   // You might want to handle this situation, depending on your requirements
//     // }
//     //
//     // // Check for unexpected error messages
//     // const unexpectedErrors = actualErrorMessages.filter(msg => !expectedErrorsArray.includes(msg));
//     // if (unexpectedErrors.length !== 0) {
//     //   console.log('unexpectedErrors', unexpectedErrors);
//     // }
//     // // If any unexpected errors are found, the test fails
//     // expect(unexpectedErrors.length === 0).toBe(true);
//
//
//     // Assertions based on the expected destination
//     const actualPathname = new URL(page.url()).pathname;
//     const expectedPathname = formDetails.expectedDestination;
//     // Check if actualPathname contains the expectedPathname
//     expect(actualPathname).toContain(expectedPathname);
//   }
// };

const fillDynamicMultiValueField = async (page: Page, dynamicMultiValueField: FormField) => {

  if (!dynamicMultiValueField.dynamic_multi) {
    return;
  }

  // eka klikataan radioo
  const labelSelector = `.option.hds-radio-button__label[for="${dynamicMultiValueField.dynamic_multi.radioSelector.value}"]`;
  // Wait for the label to exist

  console.log('Radio: id=', labelSelector);

  await page.waitForSelector(labelSelector);

  // Click on the label element
  await page.click(labelSelector);

  // Wait for the dynamically revealed elements to appear
  const revealedElementSelector = dynamicMultiValueField.dynamic_multi.revealedElementSelector.value;
  await page.waitForSelector(revealedElementSelector);

  // sit täytetään eka elementti

  await fillMultiValueField(page, dynamicMultiValueField);

  // sit painetaan lisää nappia

  // ja täytetään taas kentät.

}

/**
 * Fill multivalued field using fillFormField function to do it.
 *
 * @param page
 * @param multiValueField
 */
const fillMultiValueField = async (page: Page, multiValueField: FormField) => {
  if (multiValueField.dynamic_multi && isDynamicMultiValueField(multiValueField.dynamic_multi)) {

    const dynamicField = multiValueField.dynamic_multi.multi_field;

    // tsekataan onko ekaa multielementtiä lisätty automaagisesti
    const replacedFirstItem = replacePlaceholder(
      dynamicField.buttonSelector.resultValue ?? '',
      '[INDEX]',
      '0'
    );
    const firstItemSelector = `[data-drupal-selector="${replacedFirstItem}"]`;

    // Check if an element with the specified data-selector exists

    const firstElementExists = await page.$(firstItemSelector) !== null;

    console.log('1st item selector', firstItemSelector, firstElementExists);

    for (const [index, multiItem] of Object.entries(dynamicField.items)) {
      if (index === '0' && !firstElementExists) {
        await clickButton(page, dynamicField.buttonSelector);
      }
      if (index !== '0' && firstElementExists) {
        await clickButton(page, dynamicField.buttonSelector);
      }

      for (const fieldItem of multiItem) {
        // Parse result selector. This is the element that gets added via ajax.
        const resultSelector = replacePlaceholder(
          index.toString(),
          "[INDEX]",
          dynamicField.buttonSelector.resultValue ?? ''
        );
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
          value: replacePlaceholder(
            index.toString(),
            "[INDEX]",
            fieldItem.selector.value
          ),
          // TODO: Remove this only usage of selector.name
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

    // jos on niin täytetään eka ja lisätään seuraavat
    // jos ei oo niin klikataan eka
  }
  if (multiValueField.multi && isMultiValueField(multiValueField.multi)) {
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
          // TODO: Remove this only usage of selector.name
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

/**
 * Fill form field.
 *
 * @param page
 * @param formField
 */
const fillFormField = async (page: Page, formField: FormField) => {
  const {selector, value, role} = formField;

  // page.on('console', (message) => {
  //   console.log(`Browser Console ${message.type()}: ${message.text()}`);
  // });

  if (role === "input" && selector.type === 'data-drupal-selector') {
    const customSelector = `[data-drupal-selector="${selector.value}"]`;

    console.log('Normal item:', customSelector, value);

    // Use page.$eval to set the value of input elements
    await page.$eval(customSelector, (element, value) => {
      (element as HTMLInputElement).value = value ?? '';
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
    switch (selector.type) {
      case 'dom-id-first':
        console.log('Select: id=', selector.value);
        await page.locator(selector.value).selectOption({index: 1});
        break;

      case 'data-drupal-selector':
        const customSelector = `[${selector.type}="${selector.value}"]`;

        // Use page.$eval to set the value of input elements
        await page.$eval(customSelector, (element, value) => {
          // Cast the element to HTMLSelectElement
          const selectElement = element as HTMLSelectElement;

          let newValue = value

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
          selectElement.value = newValue ?? '';

          console.log('Select:', value, newValue);

          // Trigger the 'change' event to simulate user interaction
          const event = new Event('change', {bubbles: true});
          selectElement.dispatchEvent(event);
        }, value);
        break;

    }
  }

  if (role === 'radio') {
    switch (selector.type) {
      case 'dom-id':
        console.log('Radio: id=', selector.value);
        const radioSelector = selector.value; // Change this to the actual selector of your radio button

        // Wait for the radio button to exist
        await page.waitForSelector(radioSelector);

        try {
          // Click on the radio button
          await page.click(radioSelector);
        } catch (error) {
          console.error('Error during click:', error);
        }

        // Wait for the change event to be processed
        await page.waitForSelector(radioSelector);
        break;
      case 'dom-id-label':
        const labelSelector = `.option.hds-radio-button__label[for="${selector.value}"]`;
        // Wait for the label to exist

        console.log('Radio: id=', labelSelector);

        await page.waitForSelector(labelSelector);

        // Click on the label element
        await page.click(labelSelector);
        break;


    }
  }

};

const replacePlaceholder = (index: string, placeholder: string, value: string) => {
  return value.replace(placeholder, index);
}

const clickButton = async (page: Page, buttonSelector: Selector, formClass?: string, nextSelector?: string) => {

  page.on('console', (message) => {
    console.log(`Page log: ${message.text()}`);
  });

  if (formClass) {
    await setNoValidate(page, formClass);
  }

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

  if (buttonSelector.type === 'wizard-next') {
    try {
      console.log('Wizard next clicked');
      const continueButton = await page.getByRole('button', { name: buttonSelector.value });
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
      console.log('...ready');
    } catch (error) {
      console.error('Error during wizard next click:', error);
    }

    // console.log('Wizard next clicked');
    // const continueButton = page.getByRole('button', {name: buttonSelector.value});
    // await continueButton.click();
    // console.log('...ready');
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


const saveApplicationNumberFromBreadCrumb = async (page: Page) => {
  // Specify the selector for the breadcrumb links
  const breadcrumbSelector = '.breadcrumb__link';

  // Use page.$$ to get an array of all matching elements
  const breadcrumbLinks = await page.$$(breadcrumbSelector);

  // Get the text content of the last link
  const lastLinkText = await breadcrumbLinks[breadcrumbLinks.length - 1].textContent();

  console.log('Last link text:', lastLinkText);
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

export {
  fillProfileForm,
  fillFormField,
  clickButton,
  uploadFile,
  fillGrantsForm
};


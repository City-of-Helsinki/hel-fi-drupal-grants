import {expect, Locator, Page} from "@playwright/test";
import {FormFieldWithRemove, Selector, isDynamicMultiValueField, isMultiValueField} from "./data/test_data";
import {logger} from "./logger";
import { promises as fs } from 'fs';
import { join, dirname, extname, basename } from 'path';


/**
 * The fillFormField function.
 *
 * This function fills in a form field. It fills an input
 * by calling a relevant "fill" function based on the
 * form fields input role.
 *
 * @param page
 *   Playwright page object.
 * @param formField
 *   The form field we want to fill.
 * @param itemKey
 *   The item key.
 * @param clearBefore
 *   A boolean indicating if the field value should be cleared before filling.
 */
const fillFormField = async (
  page: Page,
  formField: Partial<FormFieldWithRemove>,
  itemKey: string,
  clearBefore: boolean = false
) => {
  const {selector, value, role} = formField;

  // Fill normal input field.
  if (role === "input" && value) {
    await fillInputField(value, selector, page, itemKey, clearBefore);
  }

  // Fill a select field.
  if (role === "select") {
    await fillSelectField(selector, page, value);
  }

  // Fill a radio field.
  if (role === 'radio') {
    await fillRadioField(selector, itemKey, page);
  }

  // Fill a checkbox field.
  if (role === 'checkbox') {
    await fillCheckboxField(selector, itemKey, page);
  }

  // Fill a multi-value field.
  if (role === 'multivalue') {
    await fillMultiValueField(page, formField, itemKey);
  }

  // Fill a dynamic multi-value field.
  if (role === 'dynamicmultivalue') {
    await fillDynamicMultiValueField(page, formField, itemKey);
  }

  // Upload a file.
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

};

/**
 * The fillInputField function.
 *
 * This function fills in a "normal" input field
 * with the given parameters.
 *
 * Available selectors are
 * - data-drupal-selector
 * - data-drupal-selector-sequential
 * - role-label
 * - role
 * - label
 * - text
 *
 * Please see documentation for additional info:
 * @see https://helsinkisolutionoffice.atlassian.net/wiki/spaces/KAN/pages/8481833123/Regressiotestit+WIP#fillInputField
 *
 * @param value
 *   Value inserted to a given field.
 * @param selector
 *   Selector object. See test_data.ts for details.
 * @param page
 *   Page object from Playwright.
 * @param itemKey
 *   Item key used in data definition.
 * @param clearBefore
 *   A boolean indicating if the field value should be cleared before filling.
 * @param simulateTyping
 *   A boolean indicating wether to simulate pressing characters one by one.
 */
async function fillInputField(
  value: string,
  selector: Selector | undefined,
  page: Page,
  itemKey: string,
  clearBefore: boolean = false,
  simulateTyping: boolean = false,
) {
  if (!selector) {
    return;
  }

  switch (selector.type) {

    // Normal data-drupal-selector.
    case "data-drupal-selector":
      const customSelector = `[data-drupal-selector="${selector.value}"]`;
      if (clearBefore) {
        await page.locator(customSelector).fill('');
      }

      if (simulateTyping) {
        await page.locator(customSelector).pressSequentially(value);
      }
      else {
        await page.locator(customSelector).fill(value);
      }
      break;

    // Press sequentially with data-drupal-selector.
    case "data-drupal-selector-sequential":
      const customSequentialSelector = `[data-drupal-selector="${selector.value}"]`;
      const element = page.locator(customSequentialSelector);
      await element.waitFor({state: 'visible'}).then(async () => {
        await element.fill('');
        await element.pressSequentially(value);
      });
      break;

    // Fill element with role & label selector.
    case 'role-label':
      if (selector.details && selector.details.role && selector.details.label) {
        // @ts-ignore
        await page.getByRole(selector.details.role, selector.details.options)
          .getByLabel(selector.details.label).fill(value);
      }
      break;

    // Fill element with role only selector.
    case 'role':
      if (selector.details && selector.details.role && selector.details.options) {
        // @ts-ignore
        await page.getByRole(selector.details.role,
          selector.details.options).fill(value);
      }
      break;

    // Fill element with the elements label.
    case 'label':
      if (selector.details && selector.details.label) {
        await page.getByLabel(selector.details.label).fill(value);
      }
      break;

    // Fill element with a text string associated with it.
    case 'text':
      if (selector.details && selector.details.text) {
        await page.getByText(selector.details.text, selector.details.options).fill(value);
      }
      break;
  }
}

/**
 * The fillSelectField function.
 *
 * This function fills in a "select" input field
 * with the given parameters.
 *
 * @param selector
 *   Selector object. See test_data.ts for details.
 * @param page
 *   Page object from Playwright.
 * @param value
 *   Value inserted to a given field.
 */
async function fillSelectField(selector: Selector | Partial<FormFieldWithRemove> | undefined, page: Page, value: string | undefined) {
  if (!selector) {
    return;
  }

  switch (selector.type) {

    // Select the first option in the select.
    case 'dom-id-first':
      if (typeof selector.value === 'string') {
        await page.locator(selector.value).selectOption({index: 1});
      }
      break;

    // Select an option by value.
    case 'by-label':
      if (selector.value && value) {
        const customSelector = `[data-drupal-selector="${selector.value}"]`;
        await page.waitForSelector(customSelector);
        await page.locator(customSelector).selectOption({label: value});
      }
      break;

    // Select element by data-drupal-selector.
    case 'data-drupal-selector':
      const customSelector = `[${selector.type}="${selector.value}"]`;

      // Use page.$eval to set the value of input elements
      await page.$eval(customSelector, (element, value) => {

        // Cast the element to HTMLSelectElement
        const selectElement = element as HTMLSelectElement;
        let newValue = value ?? 'XX';

        // Randomly select an option.
        if (value === 'use-random-value' || value == '') {
          // Fetch all options from the select element.
          const options = Array.from(selectElement.options);

          // Check if there are any options.
          if (options.length > 0) {
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
 * The fillCheckboxField function.
 *
 * This function fills in a "checkbox" input field
 * with the given parameters.
 *
 * @param selector
 *   Selector object. See test_data.ts for details.
 * @param itemKey
 *   The item key.
 * @param page
 *   Page object from Playwright.
 */
async function fillCheckboxField(selector: Selector | undefined, itemKey: string, page: Page) {
  if (!selector) {
    return;
  }

  switch (selector.type) {

    // Select element by data-drupal-selector.
    case 'data-drupal-selector':
      if (selector.name && selector.value) {
        const customSelector = `[${selector.type}="${selector.value}"]`;
        await page.locator(customSelector).click();
      }
      break;

    // Select element by text associated with it.
    case 'text':
      if (selector.details && selector.details.text) {
        await page.getByText(selector.details.text, selector.details.options).click();
      }
      break;

    // Select element by its label.
    case 'label':
      if (selector.details && selector.details.label) {
        await page.getByLabel(selector.details.label).check();
      }
      break;
  }
}

/**
 * The fillRadioField function.
 *
 * This function fills in a "radio" input field
 * with the given parameters.
 *
 * @param selector
 *   Selector object. See test_data.ts for details.
 * @param itemKey
 *   The item key.
 * @param page
 *   Page object from Playwright.
 */
async function fillRadioField(selector: Selector | undefined, itemKey: string, page: Page) {
  if (!selector) {
    return;
  }

  switch (selector.type) {

    // Fill element with a text string associated with it.
    case 'text':
      if (selector.details && selector.details.text) {
        await page.getByText(selector.details.text, selector.details.options).click();
      }
      break;

    // Fill element with dom-id.
    case 'dom-id':
      const radioSelector = selector.value;
      await page.waitForSelector(radioSelector ?? '');

      try {
        if (radioSelector != null) {
          await page.click(radioSelector);
        }
      } catch (error) {
        logger('Error during click:', error);
      }

      // Wait for the change event to be processed.
      // @ts-ignore
      await page.waitForSelector(radioSelector);
      break;

    // Fill element with dom-id-label.
    case 'dom-id-label':
      const labelSelector = `.option.hds-radio-button__label[for="${selector.value}"]`;
      await page.waitForSelector(labelSelector);
      await page.click(labelSelector);
      break;

    // Fill element with partial for attribute.
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
  await fillRadioField(radioSelector, itemKey, page);

  // Fill the multi-value field.
  if (revealedElementSelector.value) {
    await page.locator(revealedElementSelector.value).waitFor({state: 'visible'}).then(async () => {
      await fillMultiValueField(page, dynamicMultiValueField, itemKey);
    });
  } else {
    logger('No revealed element selector defined for:', itemKey);
  }
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

    // Wait for the new element to be visible and fill it out.
    await page.locator(resultItemSelector).waitFor({state: 'visible'}).then(async () => {

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
    });
  }
}

/**
 * The uploadFile function.
 *
 * This function upload a file to a
 * file input field with the given parameters. File is renamed to fix strange
 * behavior on uploaded files when run on Azure.
 *
 * @param page
 *   Page object from Playwright.
 * @param uploadSelector
 *   The input selector for uploading a file.
 * @param fileLinkSelector
 *   The resulting uploaded file link selector.
 * @param filePath
 *   The file we are uploading.
 */
const uploadFile = async (
  page: Page,
  uploadSelector: string,
  fileLinkSelector: string,
  filePath: string | undefined,
) => {
  if (!filePath) {
    logger('No file defined in', uploadSelector);
    return;
  }

  // Create a new file name
  const tempDir = dirname(filePath);
  const fileExt = extname(filePath);
  const fileName = basename(filePath, fileExt);
  const newFileName = `${fileName}-${Date.now()}${fileExt}`;
  const newFilePath = join(tempDir, newFileName);

  // Rename the original file to the new file name
  await fs.rename(filePath, newFilePath);

  try {
    const fileChooserPromise = page.waitForEvent('filechooser');
    const fileInput = page.locator(uploadSelector);
    await fileInput.click();
    const fileChooser = await fileChooserPromise;
    await fileChooser.setFiles(newFilePath);
    await fileInput.waitFor({ state: 'hidden' });
    const resultLink = page.locator(fileLinkSelector);
    await expect(fileInput).toBeHidden();
    await expect(resultLink).toBeVisible();
  } finally {
    // Rename the file back to its original name
    await fs.rename(newFilePath, filePath);
  }
};

/**
 * The clickButton function.
 *
 * This function clicks a button with the
 * passed in parameters.
 *
 * @param page
 *   Page object from Playwright.
 * @param buttonSelector
 *   The selector for the button we want to click.
 */
const clickButton = async (
  page: Page,
  buttonSelector: Selector
) => {
  let element: Locator | null = null;

  switch (buttonSelector.type) {

    case 'data-drupal-selector':
      element = page.locator(`[${buttonSelector.name}="${buttonSelector.value}"]`);
      break;

    case 'add-more-button':
      element = page.getByRole('button', {name: buttonSelector.value});
      break;

    case 'form-topnavi-link':
      element = page.locator(`li[data-webform-page="${buttonSelector.value}"] .grants-stepper__step__circle_container`);
      break;
  }

  if (element) {
    await Promise.all([
      element.waitFor({state: 'visible'}),
      element.click(),
    ]);
  }
};

/**
 * The replacePlaceholder function.
 *
 * This function replaces placeholders in a string.
 * It is used when filling dynamic multi-value and
 * multi-value fields.
 *
 * Example:
 * Passed in: 0, [INDEX], edit-toimintapaikka-items-[INDEX]
 * Would return: edit-toimintapaikka-items-0
 *
 * @param index
 *   The index we want to have instead of the placeholder.
 * @param placeholder
 *   The placeholder we want to replace.
 * @param value
 *   The string we are manipulating.
 */
const replacePlaceholder = (index: string, placeholder: string, value: string | undefined) => {
  if (!value) {
    return;
  }
  return value.replace(placeholder, index);
}

export {
  fillFormField,
  fillSelectField,
  fillInputField,
  fillCheckboxField,
  fillRadioField,
  fillMultiValueField,
  fillDynamicMultiValueField,
  clickButton,
  uploadFile,
}

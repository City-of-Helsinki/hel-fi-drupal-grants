import cloneDeep from "lodash.clonedeep"
import {logger} from "./logger";
import {hideSlidePopup} from "./helpers";
import {validateFormErrors} from "./error_validation_helpers";
import {saveObjectToEnv, extractPath} from "./helpers";
import {fillFormField, clickButton} from './input_helpers'
import {Page, expect, test} from "@playwright/test";
import {FormData, PageHandlers, FormPage} from "./data/test_data"

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
  try {
    expect(initialPathname).toMatch(expectedPattern);
  } catch (error) {
    logger(`Skipping test: Application not open in "${formDetails.title}" test.`);
    test.skip(true, 'Skip form test');
  }

  // Make sure the needed profile exists.
  expect(process.env[`profile_exists_${profileType}`], `Profile does not exist for: ${profileType}`).toBe('TRUE');

  // Store submissionUrl.
  const applicationId = await getApplicationNumberFromBreadCrumb(page);
  const submissionUrl = await extractPath(page);

  // Hide the sliding popup once.
  await hideSlidePopup(page);

  // Loop form pages
  for (const [formPageKey, formPageObject]
    of Object.entries(formDetails.formPages)) {

    // Print out form page we're on.
    logger('Form page:', formPageKey);

    // If we're on the preview page
    if (formPageKey === 'webform_preview') {
      // Compare expected errors with actual error messages on the page.
      const errorClass = '.hds-notification--error .hds-notification__body ul li';
      await validateFormErrors(page, formDetails.expectedErrors, errorClass);
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
      await page.waitForLoadState('domcontentloaded');
      await page.waitForLoadState('load');
      await page.waitForLoadState('networkidle');
      await pageHandlers[formPageKey](page, formPageObject);
    } else {
      continue;
    }

    // Make sure hidden fields are not visible.
    if (formPageObject.itemsToBeHidden) {
      await validateHiddenFields(page, formPageObject.itemsToBeHidden, formPageKey);
    }

    /**
     * If we've gathered buttons above, we take the first one and just click it.
     */
    if (buttons.length > 0) {
      const firstButton = buttons[0];
      if (firstButton.selector) {
        await clickButton(page, firstButton.selector);

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

  logger('FORM:', formDetails.title);

  // Hide the sliding popup once.
  await hideSlidePopup(page);

  // Loop form pages
  for (const [formPageKey, formPageObject] of Object.entries(formDetails.formPages)) {
    const buttons = [];
    for (const [itemKey, itemField] of Object.entries(formPageObject.items)) {
      if (itemField.role === 'button') {
        buttons.push(itemField);
      } else {
        await fillFormField(page, itemField, itemKey);
      }
    }

    // Click buttons after filling in the fields
    for (const button of buttons) {
      // @ts-ignore
      await clickButton(page, button.selector);
    }

    await page.waitForLoadState("load");

    // Compare expected errors with actual error messages on the page.
    const errorClass = '.form-item--error-message';
    await validateFormErrors(page, formDetails.expectedErrors, errorClass);

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
 * The createFormData function.
 *
 * This function takes in a base form (baseFormData)
 * and merges it with a partial overrides form (overrides).
 * Any fields under itemsToRemove or itemsToBeHidden will
 * also be removed from the newly created form.
 *
 * The function uses the lodash cloneDeep utility function
 * for cloning the "items" part of the form, in order
 * to perform a deep copy.
 *
 * @docs https://developer.mozilla.org/en-US/docs/Glossary/Deep_copy
 *
 * @param baseFormData
 *   The base form.
 * @param overrides
 *   The parts we want to override.
 */
function createFormData(baseFormData: FormData, overrides: Partial<FormData>): FormData {

  const formPages = Object.keys(baseFormData.formPages).reduce((result, pageKey) => {

    result[pageKey] = {
      ...baseFormData.formPages[pageKey],
      ...(overrides.formPages && overrides.formPages[pageKey]),
      items: {
        ...cloneDeep(baseFormData.formPages[pageKey].items),
        ...(overrides.formPages && overrides.formPages[pageKey] && overrides.formPages[pageKey].items),
      },
    };

    if (!overrides.formPages || !overrides.formPages[pageKey]) {
      return result;
    }

    // Remove any fields under itemsToRemove.
    overrides.formPages[pageKey].itemsToRemove?.forEach((itemToRemove: string) => {
      const multiValueKeyInfo = parseMultiValueKey(itemToRemove);

      // If the field is not a multi-value field, then just delete it normally.
      if (!multiValueKeyInfo) {
        return delete result[pageKey].items[itemToRemove];
      }

      /**
       * Now we know the field is either a dynamic multi-value or a normal multi-value field.
       * We can't know which one it is, so we have to check for both. Then we
       * filter out the item inside the multi-value field with a matching selector,
       * thereby removing it.
       */
      const { baseName, index, subItemKey } = multiValueKeyInfo;
      const dynamicMultiValueItems = result[pageKey]?.items[baseName]?.dynamic_multi?.multi?.items;
      const multiValueItems = result[pageKey]?.items[baseName]?.multi?.items;
      const multiItems = dynamicMultiValueItems || multiValueItems;

      if (multiItems && multiItems[index]) {
        multiItems[index] = multiItems[index].filter((item: any) => {
          return item.selector?.value !== `${baseName}-items-[INDEX]-item-${subItemKey}`;
        });
      }
    });

    // Remove any fields under itemsToBeHidden.
    overrides.formPages[pageKey].itemsToBeHidden?.forEach((itemToBeHidden: string) => {
      delete result[pageKey].items[itemToBeHidden];
    });

    return result;

  }, {} as { [pageKey: string]: FormPage });

  return {
    ...baseFormData,
    ...overrides,
    formPages,
  };
}

/**
 * The parseMultiValueKey function.
 *
 * This function attempts to parse out a
 * baseName, index and subItemKey form a form field key.
 * If all three variables are found, then we know
 * the key represents a multi-value field.
 *
 * Ex1: edit-hanke-alkaa
 * This would return null.
 *
 * Ex2: edit-myonnetty-avustus-items-0-item-issuer
 * This would return {edit-myonnetty-avustus, 0, issuer}.
 *
 * @param key
 *   The key we are parsing.
 *
 * @return { {baseName: string, index: number, subItemKey: string} | null }
 */
const parseMultiValueKey = (key: string): { baseName: string, index: number, subItemKey: string } | null => {
  const match = key.match(/^(.+)-items-(\d+)-item-(.+)$/);
  if (match && match.length === 4) {
    return {
      baseName: match[1],
      index: parseInt(match[2], 10),
      subItemKey: match[3]
    };
  }
  return null;
};

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
  }
  if (formItems['edit-bank-account-account-number-select']) {
    await page.locator('#edit-bank-account-account-number-select').selectOption({ label: formItems['edit-bank-account-account-number-select'].value });
  }
  if (formItems['edit-community-officials-items-0-item-community-officials-select']) {
    const partialCommunityOfficialLabel = formItems['edit-community-officials-items-0-item-community-officials-select'].value;
    const optionToSelect = await page.locator('option', { hasText: partialCommunityOfficialLabel }).textContent() || '';
    await page.locator('#edit-community-officials-items-0-item-community-officials-select').selectOption({ label: optionToSelect });
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
  }
  if (formItems['edit-community-officials-items-0-item-community-officials-select']) {
    const partialCommunityOfficialLabel = formItems['edit-community-officials-items-0-item-community-officials-select'].value;
    const optionToSelect = await page.locator('option', { hasText: partialCommunityOfficialLabel }).textContent() || '';
    await page.locator('#edit-community-officials-items-0-item-community-officials-select').selectOption({ label: optionToSelect });
  }
}

export {
  createFormData,
  fillProfileForm,
  fillGrantsFormPage,
  fillHakijanTiedotRegisteredCommunity,
  fillHakijanTiedotPrivatePerson,
  fillHakijanTiedotUnregisteredCommunity,
  getApplicationNumberFromBreadCrumb,
};


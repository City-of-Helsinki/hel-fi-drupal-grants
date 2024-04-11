import {logger} from "./logger";
import {hideSlidePopup} from "./helpers";
import {validateFormErrors} from "./error_validation_helpers";
import {validateHiddenFields} from "./validation_helpers";
import {saveObjectToEnv, extractPath} from "./helpers";
import {fillFormField, clickButton} from './input_helpers'
import {Page, expect, test} from "@playwright/test";
import {FormData, PageHandlers} from "./data/test_data"

/**
 * The fillGrantsFormPage function.
 *
 * This function fills form pages from given data array. Calls the page handler
 * callbacks for every page set up in the formDetails object.
 *
 * @param formKey
 *  Form data key for saving to process.env the application info.
 * @param page
 *  Playwright page object.
 * @param formDetails
 *  Form details object containing all items and pages.
 * @param formPath
 *  URL to form. Can be used for checking form validity.
 * @param formClass
 *  Form CSS class, to identify form we're on.
 * @param formID
 *   The form ID.
 * @param profileType
 *  Profile type used for this form. Private, registered.
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
  logger('FORM', formPath, formClass);

  // Navigate to form url and make sure we get there. Skip the test otherwise.
  await page.goto(formPath);
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

  // Store the submission URL.
  const submissionUrl = await extractPath(page);

  // Hide the sliding popup.
  await hideSlidePopup(page);

  // Loop form pages.
  for (const [formPageKey, formPageObject] of Object.entries(formDetails.formPages)) {
    logger('Form page:', formPageKey);

    // Wait for the page to load.
    await page.waitForLoadState('domcontentloaded');
    await page.waitForLoadState('load');
    await page.waitForLoadState('networkidle');

    // Validate form errors on the preview page.
    if (formPageKey === 'webform_preview') {
      const errorClass = '.hds-notification--error .hds-notification__body ul li';
      await validateFormErrors(page, formDetails.expectedErrors, errorClass);
    }

    // Make sure hidden fields are not visible.
    if (formPageObject.itemsToBeHidden) {
      await validateHiddenFields(page, formPageObject.itemsToBeHidden, formPageKey);
    }

    // Call a page handler to fill in the form page.
    if (pageHandlers[formPageKey]) {
      await pageHandlers[formPageKey](page, formPageObject);
    } else {
      continue;
    }

    // Collect any buttons on the page.
    const buttons = [];
    for (const itemField of Object.values(formPageObject.items)) {
      if (itemField.role === 'button') {
        buttons.push(itemField);
      }
    }

    // Continue if we don't have any buttons.
    if (!buttons.length) continue;

    // Continue if the buttons hasn't defined a selector.
    const firstButton = buttons[0];
    if (!firstButton.selector) continue;

    // Click the first button.
    await clickButton(page, firstButton.selector);

    // Verify application draft save if we had that button.
    if (firstButton.value === 'save-draft') {
      await verifyDraftSave(
        page,
        formID,
        profileType,
        submissionUrl,
        formKey
      );
    }

    // Verify application submit if we had that button.
    if (firstButton.value === 'submit-form') {
      await verifySubmit(
        page,
        formID,
        profileType,
        submissionUrl,
        formKey);
    }
  }
}

/**
 * The fillProfileForm function.
 *
 * This function fills in profile form data with the passed
 * in form details.
 *
 * @param page
 *  Playwright page object.
 * @param formDetails
 *  Form details object containing all items and pages.
 * @param formPath
 *  URL to form. Can be used for checking form validity.
 * @param formClass
 *  Form CSS class, to identify form we're on.
 */
const fillProfileForm = async (
  page: Page,
  formDetails: FormData,
  formPath: string,
  formClass: string,
) => {
  logger('FORM', formPath, formClass);

  // Navigate to form url.
  await page.goto(formPath);

  // Hide the sliding popup.
  await hideSlidePopup(page);

  // Loop form pages.
  for (const [formPageKey, formPageObject] of Object.entries(formDetails.formPages)) {
    logger('Form page:', formPageKey);

    // Loop page items and collect buttons.
    const buttons = [];
    for (const [itemKey, itemField] of Object.entries(formPageObject.items)) {
      if (itemField.role === 'button') {
        buttons.push(itemField);
      } else {
        await fillFormField(page, itemField, itemKey);
      }
    }

    // Click buttons after filling in the fields.
    for (const button of buttons) {
      if (!button.selector) continue;
      await clickButton(page, button.selector);
    }

    // Wait for the page to load after clicking buttons.
    await page.waitForLoadState("load");

    // Compare expected errors with actual error messages on the page.
    const errorClass = '.form-item--error-message';
    await validateFormErrors(page, formDetails.expectedErrors, errorClass);

    // Assertions based on the expected destination.
    const actualPathname = new URL(page.url()).pathname;
    const expectedPathname = formDetails.expectedDestination;
    expect(actualPathname).toContain(expectedPathname);
  }
};

/**
 * The verifyDraftSave function.
 *
 * This function performs initial verification that an application has
 * successfully been saved as a draft after clicking a forms
 * "save-draft" button. Information about a saved application
 * is saved to the env.
 *
 * @param page
 *  Playwright page object.
 * @param formId
 *   The form ID.
 * @param profileType
 *  Profile type used for this form. Private, registered.
 * @param submissionUrl
 *  The applications submission URL.
 * @param formKey
 *  Form data key for saving to process.env the application info.
 */
const verifyDraftSave = async (
  page: Page,
  formId: string,
  profileType: string,
  submissionUrl: string,
  formKey: string
) => {

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
 * The verifySubmit function.
 *
 * This function performs initial verification that an application has
 * successfully been submitted after clicking a forms
 * "submit-form" button. Information about a submitted application
 * is saved to the env.
 *
 * @param page
 *  Playwright page object.
 * @param formId
 *   The form ID.
 * @param profileType
 *  Profile type used for this form. Private, registered.
 * @param submissionUrl
 *  The applications submission URL.
 * @param formKey
 *  Form data key for saving to process.env the application info.
 */
const verifySubmit = async (
  page: Page,
  formId: string,
  profileType: string,
  submissionUrl: string,
  formKey: string
) => {

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
 * The fillHakijanTiedotRegisteredCommunity function.
 *
 * This function fills in profile information for registered
 * communities on the first page of an application. The function is
 * called on the first page of every registered community application test.
 *
 * @param formItems
 *   The form items we are filling in.
 * @param page
 *   Playwright page object.
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
 * The fillHakijanTiedotUnregisteredCommunity function.
 *
 * This function fills in profile information for unregistered
 * communities on the first page of an application. The function is
 * called on the first page of every unregistered community application test.
 *
 * @param formItems
 *   The form items we are filling in.
 * @param page
 *   Playwright page object.
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

/**
 * The fillHakijanTiedotPrivatePerson function.
 *
 * This function fills in profile information for private persons
 * on the first page of an application. The function is
 * called on the first page of every private person application test.
 *
 * @param formItems
 *   The form items we are filling in.
 * @param page
 *   Playwright page object.
 */
async function fillHakijanTiedotPrivatePerson(formItems: any, page: Page) {
  if (formItems['edit-bank-account-account-number-select']) {
    await page.locator('#edit-bank-account-account-number-select').selectOption({ label: formItems['edit-bank-account-account-number-select'].value });
  }
}

export {
  fillProfileForm,
  fillGrantsFormPage,
  fillHakijanTiedotRegisteredCommunity,
  fillHakijanTiedotUnregisteredCommunity,
  fillHakijanTiedotPrivatePerson,
};


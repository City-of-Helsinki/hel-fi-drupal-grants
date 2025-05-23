import {logger} from "./logger";
import {
  extractPath,
  waitForTextWithInterval,
  getApplicationNumberFromBreadCrumb,
  logCurrentUrl
} from "./helpers";
import {validateFormErrors, validateInlineFormErrors} from "./error_validation_helpers";
import {validateHiddenFields} from "./validation_helpers";
import {saveObjectToEnv} from "./env_helpers";
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
  await logCurrentUrl(page);
  const initialPathname = new URL(page.url()).pathname;
  const expectedPattern = new RegExp(`^${formDetails.expectedDestination}`);

  // If we end up on the wrong page and the application is closed, then skip the test.
  if (!expectedPattern.test(initialPathname) && await isApplicationClosed(page)) {
    logger('WARNING: The application is closed. This test will be skipped.');
    test.skip(true, 'Skip form test. Application is closed.');
  }

  // The application is open at this point. Make sure we get to the correct URL.
  expect(initialPathname, 'Error accessing the application at the expected destination.').toMatch(expectedPattern);

  // Make sure the needed profile exists.
  expect(process.env[`profile_exists_${profileType}`], `Profile does not exist for: ${profileType}`).toBe('TRUE');

  // Store the submission URL.
  const submissionUrl = await extractPath(page);

  // Log the application ID.
  const applicationId = await getApplicationNumberFromBreadCrumb(page);
  logger(`Filling form with application ID: ${applicationId}.`);

  // Loop form pages.
  for (const [formPageKey, formPageObject] of Object.entries(formDetails.formPages)) {
    logger('Form page:', formPageKey);

    // Validate form errors on the preview page.
    if (formPageKey === 'webform_preview') {
      await page.waitForLoadState('load');
      const errorClass = '.hds-notification--error .hds-notification__body ul li';
      await validateFormErrors(page, formDetails.expectedErrors, errorClass);
    }

    // Collect any buttons on the page.
    const buttons = [];
    for (const itemField of Object.values(formPageObject.items)) {
      if (itemField.role === 'button') {
        buttons.push(itemField);
      }
    }

    // Wait for the page to load and call a page handler to fill in the form page.
    if (pageHandlers[formPageKey]) {
      await page.waitForLoadState('domcontentloaded');
      await page.waitForLoadState('load');
      await page.waitForLoadState('networkidle');
      await logCurrentUrl(page);

      await pageHandlers[formPageKey](page, formPageObject);
    } else {
      continue;
    }

    // Make sure hidden fields are not visible.
    if (formPageObject.itemsToBeHidden) {
      await validateHiddenFields(page, formPageObject.itemsToBeHidden, formPageKey);
    }

    // Make sure any expected inline errors are present.
    if (formPageObject.expectedInlineErrors) {
      await validateInlineFormErrors(page, formPageObject.expectedInlineErrors);
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
  await logCurrentUrl(page);

  // Make sure we reached the correct profile form.
  await expect(page.locator('body'), 'Reached the wrong profile form.').toHaveClass(new RegExp(`\\b${formClass}\\b`));
  logger(`Reached the profile form: ${formClass}.`);

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
  logger(`Verifying draft save...`);
  await logCurrentUrl(page);
  await page.waitForURL('**/oma-asiointi');
  await expect(await page.getByText('Luonnos').first()).toBeVisible()
  await expect(await page.getByRole('link', {name: 'Muokkaa hakemusta'}).first()).toBeEnabled();
  const applicationId = await page.locator('.application-list__item--application-number').first().innerText();

  if (!applicationId) {
    logger('WARNING: Failed retrieving application ID.');
    return;
  }

  const storeName = `${profileType}_${formId}`;
  const newData = {
    [formKey]: {
      submissionUrl: submissionUrl,
      applicationId,
      status: 'DRAFT'
    }
  }
  saveObjectToEnv(storeName, newData);
  logger(`Draft save verified for application ID: ${applicationId}.`);
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
  logger(`Verifying submit...`);
  await logCurrentUrl(page);
  await page.waitForURL('**/completion');
  await expect(page.getByRole('heading', {name: 'Avustushakemus lähetetty onnistuneesti'})).toBeVisible();
  await expect(page.getByText('Lähetetty - odotetaan vahvistusta').first()).toBeVisible();

  // Attempt to locate the "Vastaanotettu" text on the page. Keep polling for 60000ms (1 minute).
  // Note: We do this instead of using Playwrights "expect" method so that test execution isn't interrupted if this fails.
  const applicationReceived = await waitForTextWithInterval(page, 'Vastaanotettu');
  if (!applicationReceived) {
    logger('WARNING: Failed to validate that the application was received.');
    return;
  }

  await page.waitForLoadState('load');
  let applicationId = await page.locator(".grants-handler__completion__item--number").innerText();
  applicationId = applicationId.replace('Hakemusnumero\n', '')

  if (!applicationId) {
    logger('WARNING: Failed retrieving application ID.');
    return;
  }

  const storeName = `${profileType}_${formId}`;
  const newData = {
    [formKey]: {
      submissionUrl: submissionUrl,
      applicationId: applicationId,
      status: 'RECEIVED'
    }
  }
  saveObjectToEnv(storeName, newData);
  logger(`Submit verified for application ID: ${applicationId}.`);
}

/**
 * The isApplicationClosed function.
 *
 * This function checks if an application is closed.
 * Applications that are closed redirect the user to the applications
 * service page and display an error message.
 *
 * @param page
 *   Playwright page object.
 */
const isApplicationClosed = async (page: Page) => {
  if (page.url().includes('/fi/tietoa-avustuksista/')) {
    const errorMessageSelector = '.hds-notification--error .hds-notification__body';
    const errorMessage = await page.locator(errorMessageSelector);

    if (errorMessage) {
      const errorMessageText = await errorMessage.innerText();
      if (errorMessageText.includes('Tämä avustushaku ei ole avoimena')) {
        return true;
      }
    }
  }
  return false;
};

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
    await page.locator('#edit-email').fill(formItems['edit-email'].value);
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

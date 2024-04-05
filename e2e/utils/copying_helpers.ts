import {expect, Page, test} from "@playwright/test";
import {FormData} from "./data/test_data";
import {logger} from "./logger";
import {getApplicationNumberFromBreadCrumb} from "./form_helpers";
import {extractPath, getObjectFromEnv, saveObjectToEnv} from "./helpers";
import {validateSubmission} from "./validation_helpers";
import {deleteDraftApplication} from "./deletion_helpers";

/**
 * The copyForm function.
 *
 * This function tests form copying. This id done by:
 *
 * 1. Calling makeFormCopy() and passing in the form
 * that is going to be copied. This is indicated by a
 * isCopyForm key in the form data.
 *
 * 2. Calling validateSubmission() and validating
 * the original forms data against the copied forms
 * data on the "Katso" page.
 *
 * 3. Calling deleteDraftApplication() and deleting
 * the copied form.
 *
 * @param originalFormKey
 *   The form variant we are copying.
 * @param profileType
 *   The profile type.
 * @param formId
 *   The form ID.
 * @param page
 *   Page object from Playwright.
 * @param originalFormDetails
 *   The form data of the form we are copying.
 * @param storedata
 *   The env form data.
 */
const copyForm = async (
  originalFormKey: string,
  profileType: string,
  formId: string,
  page: Page,
  originalFormDetails: FormData,
  storedata: any
) => {

  // Skip this test if the normal "Form" test failed for the form we are copying.
  if (storedata === undefined || storedata[originalFormKey] === undefined) {
    logger(`Skipping copy test: No env data stored after the "${originalFormDetails.title}" test.`);
    test.skip(true, 'Skip copy test');
  }
  logger('Performing form copy test...');

  // Set up a key for the copied form.
  const COPIED_FORM_KEY = 'copied_form';

  // Get the original forms application ID.
  const thisStoreData = storedata[originalFormKey];
  const originalApplicationId = thisStoreData.applicationId;

  // Copy the original form.
  await makeFormCopy(originalApplicationId, COPIED_FORM_KEY, profileType, formId, page);

  // Get the new stored data containing the copied form.
  const newStoreData = getObjectFromEnv(profileType, formId);

  // Verify the contents of the copied form against the original form.
  logger('Validating copied application...');
  await validateSubmission(COPIED_FORM_KEY, page, originalFormDetails, newStoreData);

  // Delete the copied form.
  logger('Deleting copied application...');
  await deleteDraftApplication(COPIED_FORM_KEY, page, originalFormDetails, newStoreData);
}

/**
 * The makeFormCopy function.
 *
 * This function copies a form. This is done by:
 *
 * 1. Navigating to the "Katso" page of the form
 * we want to copy (the original form).
 *
 * 2.Clicking the "Kopioi hakemus" button on the
 * "Katso" page of the original form.
 *
 * 3. Getting redirected to the new form and storing
 * its application ID and submission URL in the
 * .env data.
 *
 * 4. Saving the new form as a draft.
 *
 * @param originalApplicationId
 *   The original forms application ID.
 * @param copiedFormKey
 *   The form key of the copied form.
 * @param profileType
 *   The profile type.
 * @param formId
 *   The form ID.
 * @param page
 *   Page object from Playwright.
 */
const makeFormCopy = async (
  originalApplicationId: string,
  copiedFormKey: string,
  profileType: string,
  formId: string,
  page: Page,
) => {

  // Go to the "Katso" page of the original form we are copying.
  const viewPageURL = `/fi/hakemus/${originalApplicationId}/katso`;
  await page.goto(viewPageURL);
  logger(`Navigated to: ${viewPageURL}.`);

  // Make sure we get there.
  const applicationIdContainer = await page.locator('.webform-submission__application_id');
  const applicationIdContainerText = await applicationIdContainer.textContent();
  expect(applicationIdContainerText).toContain(originalApplicationId);

  // Copy the original application.
  logger(`Copying application: ${originalApplicationId}...`);
  await page.locator('span', { hasText: 'Kopioi hakemus' }).click();
  await page.locator('span', { hasText: 'Käytä hakemusta pohjana' }).click();

  // Wait for a redirect to the new form and store the new application ID and submission URL.
  await page.waitForURL('**/muokkaa');
  const newApplicationId = await getApplicationNumberFromBreadCrumb(page);
  const submissionUrl = await extractPath(page);

  // Save the new form as a draft.
  await page.locator('[data-drupal-selector="edit-actions-draft"]').click();
  await page.waitForURL('**/katso');

  // Store the copied forms data to the env.
  const storeName = `${profileType}_${formId}`;
  const newData = {
    [copiedFormKey]: {
      submissionUrl: submissionUrl,
      applicationId: newApplicationId,
      status: 'DRAFT'
    }
  }
  saveObjectToEnv(storeName, newData);
  logger(`Application copied. New application ID: ${newApplicationId}.`);
}

export {
  copyForm,
}
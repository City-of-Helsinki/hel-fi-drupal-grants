import {expect, Page, test} from "@playwright/test";
import {FormData} from "./data/test_data";
import {logger} from "./logger";
import {logCurrentUrl} from "./helpers";

/**
 * The DeletionMethod enum.
 *
 * This enum represents the available deletion
 * methods.
 *
 * SubmissionUrl = Delete an application by navigating
 * to its submission URL and clicking "Delete" there.
 *
 * ApplicationId = Delete an application by finding the application
 * from the "Oma asiointi" application listing and clicking "Delete".
 */
enum DeletionMethod {
  SubmissionUrl,
  ApplicationId
}

/**
 * The deleteDraftApplication function.
 *
 * The main draft deletion function. This function
 * randomly decides which deletion method to use. This
 * is done so that we can test all available approaches.
 *
 * @param formKey
 *   The form variant key.
 * @param page
 *   Page object from Playwright.
 * @param formDetails
 *   The form data.
 * @param storedata
 *   The env form data.
 */
const deleteDraftApplication = async (formKey: string, page: Page, formDetails: FormData, storedata: any) => {
  if (storedata === undefined || storedata[formKey] === undefined) {
    logger(`Skipping deletion test: No env data stored after the "${formDetails.title}" test.`);
    test.skip(true, 'Skip deletion test');
  }

  const thisStoreData = storedata[formKey];
  if (thisStoreData.status !== 'DRAFT') {
    return;
  }
  logger(`Deleting draft application with application ID: ${thisStoreData.applicationId}...`);
  const method = Math.random() < 0.5 ? DeletionMethod.SubmissionUrl : DeletionMethod.ApplicationId;
  if (method === DeletionMethod.SubmissionUrl) {
    logger(`Deleting via submission URL: ${thisStoreData.submissionUrl}.`);
    await deleteUsingSubmissionUrl(page, thisStoreData.submissionUrl, thisStoreData.applicationId);
  } else {
    logger(`Deleting via Oma asiointi with ID: ${thisStoreData.applicationId}.`);
    await deleteUsingApplicationId(page, thisStoreData.applicationId);
  }
}

/**
 * The deleteUsingSubmissionUrl function.
 *
 * This function deletes a draft application by:
 * 1. Navigating to the submission URL.
 * 2. Clicking the "Delete draft" button.
 * 3. Accepting the dialog popup.
 * 4. Waiting for a redirect to "Oma asiointi".
 * 5. Checking that a "Luonnos poistettu" message is displayed.
 *
 * @param page
 *   Page object from Playwright.
 * @param submissionUrl
 *   The submission URL (e.g. /fi/hakemus/liikunta_toiminta_ja_tilankaytto/1391/muokkaa).
 * @param applicationId
 *   The application ID (e.g. LOCALT-060-0000202).
 */
const deleteUsingSubmissionUrl = async (page: Page, submissionUrl: string, applicationId: string) => {
  await page.goto(submissionUrl);
  await logCurrentUrl(page);
  await page.waitForURL('**/muokkaa');
  await page.locator('#webform-button--delete-draft').click();
  page.once('dialog', async dialog => {
    await dialog.accept();
  });
  await logCurrentUrl(page);
  await page.waitForURL('/fi/oma-asiointi');
  await validateDeletionNotification(page, 'Submission URL.', applicationId);
}

/**
 * The deleteUsingApplicationId function.
 *
 * This function deletes a draft application by:
 * 1. Navigating to the "Oma asiointi" page.
 * 2. Clicking the "Delete application" link for the desired application
 *    in the application listing.
 * 3. Waiting for the page to reload.
 * 4. Checking that a "Luonnos poistettu" message is displayed.
 *
 * @param page
 *   Page object from Playwright.
 * @param applicationId
 *   The application ID (e.g. LOCALT-060-0000202).
 */
const deleteUsingApplicationId = async (page: Page, applicationId: string) => {
  await page.goto('/fi/oma-asiointi');
  await logCurrentUrl(page);
  await page.waitForURL('**/oma-asiointi');
  await page.locator(`.application-delete-link-${applicationId}`).click();
  await page.waitForLoadState('load');
  await logCurrentUrl(page);
  await validateDeletionNotification(page, 'Application ID on Oma asiointi page.', applicationId);
}

/**
 * The validateDeletionNotification function.
 *
 * This function looks for a "Luonnos poistettu" message
 * on the page. Throws an error if the message is not found.
 *
 * @param page
 *   Page object from Playwright.
 * @param message
 *   Message indicating which deleting method was used.
 * @param applicationId
 *   The application ID (e.g. LOCALT-060-0000202).
 */
const validateDeletionNotification = async (page: Page, message: string, applicationId: string) => {
  logger(`Verifying deletion of application with application ID: ${applicationId}...`);
  const notificationContainer = await page.locator('.messages__container .hds-notification.hds-notification--info');
  await expect(notificationContainer, "Failed to delete draft application").toContainText("Luonnos poistettu");
  logger(`Draft application deleted. Application deleted with: ${message}`);
}

export {
  deleteDraftApplication,
}

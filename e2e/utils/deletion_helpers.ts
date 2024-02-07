import {Page} from "@playwright/test";
import {FormData} from "./data/test_data";
import {logger} from "./logger";

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
 * is done so that we can test all available options.
 *
 * @param formKey
 *   The form variant key.
 * @param page
 *   The browser page.
 * @param formDetails
 *   The form data.
 * @param storedata
 *   The env form data.
 */
const deleteDraftApplication = async (formKey: string, page: Page, formDetails: FormData, storedata: any) => {
  const thisStoreData = storedata[formKey];
  if (thisStoreData.status !== 'DRAFT') {
    return;
  }

  const method = Math.random() < 0.5 ? DeletionMethod.SubmissionUrl : DeletionMethod.ApplicationId;
  if (method === DeletionMethod.SubmissionUrl) {
    logger('Deleting draft application with submission URL.')
    await deleteUsingSubmissionUrl(page, thisStoreData.submissionUrl);
  } else {
    logger('Deleting draft application with Application ID.')
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
 *   The browser page.
 * @param submissionUrl
 *   The submission URL (e.g. /fi/hakemus/liikunta_toiminta_ja_tilankaytto/1391/muokkaa).
 */
const deleteUsingSubmissionUrl = async (page: Page, submissionUrl: string) => {
  await page.goto(submissionUrl);
  await page.locator('#webform-button--delete-draft').click();
  page.once('dialog', async dialog => {
    logger(dialog.message());
    await dialog.accept();
  });
  await page.waitForURL("/fi/oma-asiointi");
  await validateDeletionNotification(page, 'Submission URL.');
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
 *   The browser page.
 * @param applicationId
 *   The application ID (e.g. LOCALT-060-0000202).
 */
const deleteUsingApplicationId = async (page: Page, applicationId: string) => {
  await page.goto("/fi/oma-asiointi");
  await page.locator(`.application-delete-link-${applicationId}`).click();
  await page.waitForLoadState();
  await validateDeletionNotification(page, 'Application ID on Oma asiointi page.');
}

/**
 * The validateDeletionNotification function.
 *
 * This function looks for a "Luonnos poistettu" message
 * on the page. Throws an error if the message is not found.
 *
 * @param page
 *   The browser page.
 * @param message
 *   Message indicating which deleting method was used.
 */
const validateDeletionNotification = async (page: Page, message: string) => {
  const notificationContainer = await page.locator('.hds-notification.hds-notification--info');
  const notificationText = await notificationContainer.textContent({timeout: 1000});
  if (notificationText && notificationText.includes("Luonnos poistettu.")) {
    logger("Draft application deleted. Application deleted with:", message);
  } else {
    throw new Error(`Failed to delete draft application.`);
  }
}

export {
  deleteDraftApplication,
}

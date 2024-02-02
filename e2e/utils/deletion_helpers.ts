import {Page} from "@playwright/test";
import {FormData} from "./data/test_data";
import {logger} from "./logger";

const deleteDraftApplication = async (formKey: string, page: Page, formDetails: FormData, storedata: any) => {
  const thisStoreData = storedata[formKey];

  if (thisStoreData.status !== 'DRAFT') {
    return;
  }

  // Randomly decide how we delete the application.
  const method = Math.random() < 0.5 ? 1 : 2;

  if (method === 1) {
    await deleteUsingSubmissionUrl(page, thisStoreData.submissionUrl);
  } else {
    await deleteUsingApplicationId(page, thisStoreData.applicationId);
  }
}

/**
 * The deleteUsingSubmissionUrl function.
 *
 * @param page
 * @param submissionUrl
 */
const deleteUsingSubmissionUrl = async (page: Page, submissionUrl: string) => {
  await page.goto(submissionUrl);
  await page.locator('#webform-button--delete-draft').getByText('Poista luonnos').click();
  page.once('dialog', async dialog => {
    logger(dialog.message());
    await dialog.accept();
  });
  await page.waitForURL("/fi/oma-asiointi");
  await validateDeletionNotification(page, 'DELETED WITH SUBMISSION URL');
}

/**
 * The deleteUsingApplicationId function.
 *
 * @param page
 * @param applicationId
 */
const deleteUsingApplicationId = async (page: Page, applicationId: string) => {
  await page.goto("/fi/oma-asiointi");
  await page.locator(`#${applicationId}`).getByText('Poista hakemus').click();
  await page.waitForLoadState();
  await validateDeletionNotification(page, 'DELETED WITH APPLICATION ID');
}

/**
 * The validateDeletionNotification function.
 *
 * @param page
 * @param message
 */
const validateDeletionNotification = async (page: Page, message: string) => {
  await page.waitForSelector('.hds-notification.hds-notification--info');
  const notificationText = await page.innerText('.hds-notification.hds-notification--info');
  if (notificationText.includes("Luonnos poistettu.")) {
    logger("Draft application deletion confirmed:", message);
  } else {
    throw new Error("Deletion notification not found or incorrect.");
  }
}

export {
  deleteDraftApplication,
}

import {expect, Page, test} from "@playwright/test";
import {logCurrentUrl} from "./helpers";
import {logger} from "./logger";
import {FormData} from "./data/test_data";


/**
 * The verifyDraftButton function.
 *
 * This function verifies that a draft application has a
 * "Save as draft" button on the "edit application" page.
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
const verifyDraftButton = async (
  formKey: string,
  page: Page,
  formDetails: FormData,
  storedata: any
) => {
  if (storedata === undefined || storedata[formKey] === undefined) {
    logger(`Skipping verify draft button test: No env data stored after the "${formDetails.title}" test.`);
    test.skip(true, 'Skip verify draft button test');
    return;
  }

  const {applicationId, submissionUrl} = storedata[formKey];
  logger(`Performing verify draft button test: ${applicationId}...`);

  await page.goto(submissionUrl);
  await logCurrentUrl(page);
  await page.waitForURL('**/muokkaa');
  logger(`Navigated to: ${submissionUrl}.`);

  const draftButton = await page.locator('[data-drupal-selector="edit-actions-draft"]').count();
  const errorMessage = `No draft button found at: ${submissionUrl}. Application ID: ${applicationId}`;
  await expect(draftButton, errorMessage).toBeTruthy();
  logger('Draft button verified.')
}

export {
  verifyDraftButton,
}

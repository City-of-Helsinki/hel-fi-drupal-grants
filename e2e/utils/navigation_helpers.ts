import {Page} from "@playwright/test";
import {Selector} from "./data/test_data";
import {logCurrentUrl} from "./helpers";
import {logger} from "./logger";
import {clickButton} from "./input_helpers";

/**
 * The goToSubmissionUrl function.
 *
 * This function navigates to an applications
 * submission URL.
 *
 * @param page
 *   Page object from Playwright.
 * @param submissionUrl
 *   A submission URL.
 */
const goToSubmissionUrl = async (page: Page, submissionUrl: string) => {
  await page.goto(submissionUrl);
  await logCurrentUrl(page);
  await page.waitForURL('**/muokkaa');
  logger(`Navigated to: ${submissionUrl}.`);
};

/**
 * The navigateToApplicationPage function.
 *
 * This function navigate to a given page
 * (formPageKey) on an application.
 *
 * @param page
 *   Page object from Playwright.
 * @param formPageKey
 *   A form pages key (the page we are navigating to).
 */
const navigateToApplicationPage = async (page: Page, formPageKey: string) => {
  const applicantDetailsLink: Selector = {
    type: 'form-topnavi-link',
    name: 'data-webform-page',
    value: formPageKey,
  }
  await clickButton(page, applicantDetailsLink);
  await page.waitForLoadState('load');
  await logCurrentUrl(page);
  logger(`Loaded page: ${formPageKey}.`);
};

export {
  goToSubmissionUrl,
  navigateToApplicationPage
};

import {expect, Page, test} from "@playwright/test";
import {FormData, TooltipsList} from "./data/test_data";
import {logger} from "./logger";
import {goToSubmissionUrl, navigateToApplicationPage} from "./navigation_helpers";

/**
 * The validateTooltips function.
 *
 * This function performs the tooltip validation test.
 * This is done by:
 *
 * 1. Navigating to the submission URL with goToSubmissionUrl.
 * 2. Looping all form pages and checking if tooltipsToValidate is set.
 * 3. Validating tooltips with validateTooltipsOnPage.
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
const validateTooltips = async (
  formKey: string,
  page: Page,
  formDetails: FormData,
  storedata: any
) => {
  if (storedata === undefined || storedata[formKey] === undefined) {
    logger(`Skipping tooltip validation test: No env data stored after the "${formDetails.title}" test.`);
    test.skip(true, 'Skip bank account swap test');
    return;
  }

  const {applicationId, submissionUrl} = storedata[formKey];
  logger(`Performing tooltip validation for application: ${applicationId}...`);
  await goToSubmissionUrl(page, submissionUrl);

  for (const [formPageKey, formPageObject] of Object.entries(formDetails.formPages)) {
    if (!formPageObject.tooltipsToValidate) continue;
    let tooltipsToValidate = formPageObject.tooltipsToValidate;
    await validateTooltipsOnPage(page, formPageKey, tooltipsToValidate);
  }

  logger('Tooltips validated.');
}

/**
 * The validateTooltipsOnPage function.
 *
 * This function performs validates the tooltips
 * on a given page. This is done by:
 *
 * 1. Navigating to the desired application page with navigateToApplicationPage.
 * 2. Validating the tooltips by clicking the corresponding
 *    aria-label and checking the message.
 *
 * @param page
 *   Page object from Playwright.
 * @param formPageKey
 *   A form pages key.
 * @param tooltipsToValidate
 *   A list of tooltips to validate on a give page.
 */
const validateTooltipsOnPage = async (
  page: Page,
  formPageKey: string,
  tooltipsToValidate: TooltipsList,
) => {
  await navigateToApplicationPage(page, formPageKey);

  for (const tooltip of tooltipsToValidate) {
    logger(`Validating tooltip: "${tooltip.aria_label}" with message: "${tooltip.message}".`);
    await page.locator(`[aria-label="${tooltip.aria_label}"]:visible`).click();
    await page.waitForTimeout(1000);
    const tooltipContent = await page.locator('.tippy-content .webform-element-help--content').innerText();
    expect(tooltipContent.trim()).toBe(tooltip.message);
  }
};

export { validateTooltips };


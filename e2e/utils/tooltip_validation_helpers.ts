import {expect, Page, test} from "@playwright/test";
import {FieldSwapItemList, FormData, FormPage, Selector, TooltipsList} from "./data/test_data";
import {logger} from "./logger";
import {clickButton, fillFormField} from "./input_helpers";
import {logCurrentUrl} from "./helpers";

/**
 * The validateTooltips function.
 *
 * TBD.
 *
 * 1. Navigating to the submission URL with goToSubmissionUrl.
 * 2. Looping all form pages and checking if itemsToSwap is set.
 * 3. Swapping field values with swapFieldValuesOnPage.
 * 4. Saving the form as draft with saveAsDraft.
 * 5. Validating that the values were changed with validateFormData.
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
 * This function performs the swapping of field
 * values on a given application page. This is done by:
 *
 * 1. Navigating to the desired application page with navigateToApplicationPage.
 * 2. Swapping the values of the fields inside itemsToSwap
 *    by calling fillFormField after the field values have been manipulated.
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
    logger(`Validating tooltip: ${tooltip.aria_label} with message: ${tooltip.message}`);
    await page.locator(`[aria-label="${tooltip.aria_label}"]`).click();
    await page.waitForTimeout(1000);
    const tooltipContent = await page.locator('.tippy-content .webform-element-help--content').innerText();
    expect(tooltipContent.trim()).toBe(tooltip.message);
  }
};

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

export { validateTooltips };


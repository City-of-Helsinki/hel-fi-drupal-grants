import {expect, Page} from "@playwright/test";
import {logger} from "./logger";
import {ExpectedInlineError} from "./data/test_data";

/**
 * The validateFormErrors function.
 *
 * This function validates that the expected errors are
 * present on a form and no unexpected errors appear.
 *
 * @param page
 *   Playwright page object.
 * @param expectedErrors
 *   An object containing the expected form errors.
 * @param errorContainerClass
 *   A class representing the error container.
 */
const validateFormErrors = async (page: Page, expectedErrors: Object, errorContainerClass: string) => {
  logger('Validating form errors...');

  // Retrieve all actual error messages from the page.
  const actualErrorMessages = await page.locator(errorContainerClass).evaluateAll(elements =>
    elements.map(element => element.textContent?.trim()).filter(text => text)
  );

  // Convert expected errors map to an array of expected error messages.
  const expectedErrorMessages = Object.values(expectedErrors);

  // Find discrepancies between expected and actual error messages.
  const notFoundErrors = expectedErrorMessages.filter(expectedMessage =>
    !actualErrorMessages.some(actualMessage => actualMessage && actualMessage.includes(expectedMessage))
  );

  const unexpectedErrors = actualErrorMessages.filter(actualMessage =>
    !expectedErrorMessages.some(expectedMessage => actualMessage && actualMessage.includes(expectedMessage))
  );

  // Assert that no expected errors are missing.
  if (notFoundErrors.length > 0) {
    logger('Missing expected errors:', notFoundErrors);
    logger('All errors on the page:', actualErrorMessages);
    logger('All expected errors:', expectedErrorMessages);
    expect(notFoundErrors).toEqual([]);
  }

  // Assert that no unexpected errors are present.
  if (unexpectedErrors.length > 0) {
    logger('Unexpected errors found:', unexpectedErrors);
    logger('All errors on the page:', actualErrorMessages);
    logger('All expected errors:', expectedErrorMessages);
    expect(unexpectedErrors).toEqual([]);
  }

  // In cases where no errors are expected, this ensures the actual errors are indeed zero.
  if (expectedErrorMessages.length === 0) {
    expect(actualErrorMessages.length).toBe(0);
  }

  logger('Errors validated successfully.')
}

/**
 * The validateInlineFormErrors function.
 *
 * This function validates that the expected inline form
 * errors are present on the page. The validation is done
 * by refreshing the page and checking the inline errors.
 *
 * @param page
 *   Playwright page object.
 * @param expectedInlineErrors
 *   An object containing the expected inline form errors.
 */
const validateInlineFormErrors = async (page: Page, expectedInlineErrors: ExpectedInlineError[]) => {
  logger('Validating inline form errors...');

  // Refresh the page by clicking the active navigation item.
  await page.locator('.progress-step.is-active').click();
  await page.waitForLoadState('load');

  // Validate the inline form errors after the page refresh.
  for (const inlineError of expectedInlineErrors) {
    logger(`Validating inline form error: ${inlineError.errorMessage}`)
    const locator = await page.locator(inlineError.selector).locator('.form-item--error-message');
    await expect(locator, 'Failed to validate inline form error.').toHaveText(inlineError.errorMessage, {useInnerText: true});
  }

  logger('Inline errors validated successfully.');
}

export {
  validateFormErrors,
  validateInlineFormErrors,
}

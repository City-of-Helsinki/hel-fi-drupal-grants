import {expect, Page} from "@playwright/test";
import {logger} from "./logger";

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

export {
  validateFormErrors,
}

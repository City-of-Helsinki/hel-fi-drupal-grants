import {expect, Page} from "@playwright/test";
import {ComponentDetails} from "./data/test_data";
import {logger} from "./logger";

/**
 * The validateComponent function.
 *
 * This function validates components data that's
 * originally set in public_page_data.ts. This is
 * done by:
 *
 * 1. Making sure that a given amount of components
 * are present on the page.
 *
 * 2. Making sure that each component has the required
 * elements.
 *
 * 3. If requested, making sure that an element has certain
 * text content.
 *
 * @param page
 *  Playwright page object.
 * @param component
 *   A page component, like a paragraph or hero.
 */
const validateComponent = async (page: Page, component: ComponentDetails) => {
  logger('Validating component...');

  // Extract data.
  const { containerClass, occurrences = 1, elements } = component;

  // Check the count for each component.
  logger(`Expecting ${occurrences} occurrences of "${containerClass}".`);
  const componentCount = await page.locator(containerClass).count();
  await expect(componentCount, `Expected ${occurrences} occurrences of "${containerClass}" but found ${componentCount}.`).toBe(occurrences);

  // Validate the elements inside each component.
  for (let index = 0; index < occurrences; index++) {
    for (const element of elements) {
      const { selector, countExact, countAtLeast, expectedText } = element;

      if (countExact !== undefined) {
        await validateExactCount(page, containerClass, selector, countExact, index);
      }

      if (countAtLeast !== undefined) {
        await validateAtLeastCount(page, containerClass, selector, countAtLeast, index);
      }

      if (expectedText && expectedText.length) {
        await validateTextContent(page, containerClass, selector, expectedText, index);
      }
    }
  }
  logger('Component validated. \n');
};

/**
 * The validateExactCount function.
 *
 * This function validates that a containerClass contains
 * exactly countExact of selector.
 *
 * @param page
 *   Playwright page object.
 * @param containerClass
 *   The container class.
 * @param selector
 *   The element selector.
 * @param countExact
 *   The "exact" element count.
 * @param index
 *   The :nth index of containerClass.
 */
const validateExactCount = async (page: Page, containerClass: string, selector: string, countExact: number, index: number) => {
  logger(`Expecting ${countExact} occurrences of "${selector}" in instance "${index + 1}" of "${containerClass}"`);
  const elementCount = await page.locator(containerClass).nth(index).locator(selector).count();
  const errorMessage = `Expected exactly ${countExact} of "${selector}" in occurrence ${index + 1} of "${containerClass}" but found ${elementCount}.`;
  await expect(elementCount, errorMessage).toBe(countExact);
}

/**
 * The validateAtLeastCount function.
 *
 * This function validates that a containerClass contains
 * at least countAtLeast of selector.
 *
 * @param page
 *   Playwright page object.
 * @param containerClass
 *   The container class.
 * @param selector
 *   The element selector.
 * @param countAtLeast
 *   The "at least" element count.
 * @param index
 *   The :nth index of containerClass.
 */
const validateAtLeastCount = async (page: Page, containerClass: string, selector: string, countAtLeast: number, index: number) => {
  logger(`Expecting at least ${countAtLeast} occurrences of "${selector}" in instance "${index + 1}" of "${containerClass}"`);
  const elementCount = await page.locator(containerClass).nth(index).locator(selector).count();
  const errorMessage = `Expected at least ${countAtLeast} of "${selector}" in occurrence ${index + 1} of "${containerClass}" but found ${elementCount}.`;
  await expect(elementCount, errorMessage).toBeGreaterThanOrEqual(countAtLeast);
}

/**
 * The validateTextContent function.
 *
 * This function validates that a selector
 * has a given text.
 *
 * @param page
 *   Playwright page object.
 * @param containerClass
 *   The container class.
 * @param selector
 *   The element selector.
 * @param expectedText
 *   The expected text inside selector.
 * @param index
 *   The :nth index of containerClass.
 */
const validateTextContent = async (page: Page, containerClass: string, selector: string, expectedText: string[], index: number) => {
  for (const text of expectedText) {
    logger(`Expecting to find text "${text}" in "${selector}" in instance "${index + 1}" of "${containerClass}".`);
    const elementsWithText = await page.locator(containerClass).nth(index).locator(selector).filter({ hasText: text }).count();
    const errorMessage = `Expected text "${text}" for "${selector}" in occurrence ${index + 1} of "${containerClass}" but found ${elementsWithText} instances.`;
    await expect(elementsWithText, errorMessage).toBeGreaterThan(0);
  }
}


/**
 * The validatePageTitle function.
 *
 * This function validates that the page title
 * is set to "*** | Helsingin kaupunki".
 *
 * @param page
 *   Playwright page object.
 */
const validatePageTitle = async (page: Page) => {
  logger(`Validating page title...`);
  const title = await page.title();
  const titlePattern = /[^|]+\| Helsingin kaupunki$/;
  await expect(title, `The page title '${title}' does not end with '| Helsingin kaupunki'.`).toMatch(titlePattern);
  logger('Page title validated.');
};

/**
 * The getReceivedApplicationDateValues function.
 *
 * This function is used to for getting the dates
 * from the submitted applications on the Oma-asiointi page.
 *
 * @param page
 *   Playwright page object.
 *
 * @return
 *   A promise containing an array of dates.
 */
const getReceivedApplicationDateValues = async (page: Page): Promise<Date[]> => {
  const receivedApplications = await page.locator("#oma-asiointi__sent .application-list__item--submitted").all();

  const datePromises = receivedApplications.map(async a => {
    const innerText = await a.innerText();
    const trimmedText = innerText.trim();
    return new Date(trimmedText)
  })
  return await Promise.all(datePromises)
}

/**
 * The getReceivedApplicationCount function.
 *
 * This function returns the count of received applications
 * on the Oma-asiointi page.
 *
 * @param page
 *   Playwright page object.
 *
 * @return
 *   A promise containing an number.
 */
const getReceivedApplicationCount = async (page: Page): Promise<Number> => {
  return await page.locator('.application-list [data-status="RECEIVED"]').count();
}

/**
 * The isAscending function.
 *
 * This function checks if a given date array
 * is in ascending order.
 *
 * @param dates
 *   An array of dates.
 *
 * @return
 *   A boolean indicating if the passed in array is in ascending order.
 */
const isAscending = function (dates: Date[]): boolean {
  return dates.every((x, i) => i === 0 || x >= dates[i - 1]);
};

/**
 * The isDescending function.
 *
 * This function checks if a given date array
 * is in descending order.
 *
 * @param dates
 *  An array of dates.
 *
 * @return
 *   A boolean indicating if the passed in array is in descending order.
 */
const isDescending = function (dates: Date[]): boolean {
  return dates.every((x, i) => i === 0 || x <= dates[i - 1]);
};

export {
  validateComponent,
  validatePageTitle,
  getReceivedApplicationDateValues,
  getReceivedApplicationCount,
  isDescending,
  isAscending,
}

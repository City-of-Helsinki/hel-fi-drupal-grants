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
 * @param page
 *  Playwright page object.
 * @param component
 *   A page component, like a paragraph or hero.
 */
const validateComponent = async (page: Page, component: ComponentDetails) => {
  logger('Validating component...');

  // Extract data.
  const { containerClass, occurrences = 1, elements } = component;
  logger(`Expecting ${occurrences} occurrences of "${containerClass}" `);

  // Check the count for each component.
  const componentCount = await page.locator(containerClass).count();
  await expect(componentCount, `Expected ${occurrences} occurrences of "${containerClass}" but found ${componentCount}.`).toBe(occurrences);

  // For each component, check the count of the required elements.
  for (let i = 0; i < occurrences; i++) {
    for (const element of elements) {
      const { selector, count } = element;
      logger(`Expecting ${count} occurrences "${selector}" in instance "${i + 1}" of "${containerClass}"`);

      const elementCount = await page.locator(`${containerClass} >> nth=${i} >> ${selector}`).count();
      await expect(elementCount,  `Expected ${count} of "${selector}" in occurrence ${i + 1} of "${containerClass}" but found ${elementCount}.`).toBe(count);
    }
  }
  logger('Component validated! \n');
};

/**
 * The validatePageTitle function.
 *
 * This function validates that the page title
 * is set to "*** | Helsingin kaupunki".
 *
 * @param page
 *  Playwright page object.
 */
const validatePageTitle = async (page: Page) => {
  logger(`Validating page title...`);
  const title = await page.title();
  const titlePattern = /.*\| Helsingin kaupunki$/;
  await expect(title, `The page title '${title}' does not end with '| Helsingin kaupunki'.`).toMatch(titlePattern);
  logger('Page title validated!');
};

/**
 * The getReceivedApplicationDateValues function.
 *
 * This function is used to for getting the dates
 * from the submitted applications on the Oma-asiointi page.
 *
 * @param page
 *  Playwright page object.
 */
const getReceivedApplicationDateValues = async (page: Page) => {
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
 *  Playwright page object.
 */
const getReceivedApplicationCount = async (page: Page) => {
  return await page.locator('.application-list [data-status="RECEIVED"]').count();
}

/**
 * The isAscending function.
 *
 * This function checks if a given date array
 * is in ascending order.
 *
 * @param dates
 *  An array of dates.
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

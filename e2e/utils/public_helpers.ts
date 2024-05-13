import {expect, Page} from "@playwright/test";
import {ComponentDetails} from "./data/test_data";
import {logger} from "./logger";

/**
 * The validateComponent function.
 *
 * @param page
 * @param component
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
 * @param page
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
 * @param page
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
 * @param page
 */
const getReceivedApplicationCount = async (page: Page) => {
  return await page.locator('.application-list [data-status="RECEIVED"]').count();
}

/**
 * The isAscending function.
 *
 * @param dates
 */
const isAscending = function (dates: Date[]): boolean {
  return dates.every((x, i) => i === 0 || x >= dates[i - 1]);
};

/**
 * The isDescending function.
 *
 * @param dates
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

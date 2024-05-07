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
  const { className, occurrences = 1, elements } = component;
  logger(`Expecting ${occurrences} occurrences of "${className}" `);

  // Check the count for each component.
  const componentCount = await page.locator(className).count();
  await expect(componentCount, `Expected ${occurrences} occurrences of "${className}" but found ${componentCount}.`).toBe(occurrences);

  // For each component, check the count of the required elements.
  for (let i = 0; i < occurrences; i++) {
    for (const element of elements) {
      const { selector, count } = element;
      logger(`Expecting ${count} occurrences "${selector}" in instance "${i + 1}" of "${className}"`);

      const elementCount = await page.locator(`${className} >> nth=${i} >> ${selector}`).count();
      await expect(elementCount,  `Expected ${count} of "${selector}" in occurrence ${i + 1} of "${className}" but found ${elementCount}.`).toBe(count);
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

export {
  validateComponent,
  validatePageTitle
}

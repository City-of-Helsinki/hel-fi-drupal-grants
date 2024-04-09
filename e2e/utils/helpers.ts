import {Locator, Page} from "@playwright/test";
import {logger} from "./logger";

/**
 * The slowLocator function.
 *
 * This function returns a "slow" page locator that
 * waits before 'click' and 'fill' requests.
 *
 * @param page
 *  Playwright page object
 * @param waitInMs
 *  The wait time in ms for the locator.
 */
function slowLocator(page: Page, waitInMs: number): (...args: any[]) => Locator {
  // Grab original.
  const l = page.locator.bind(page);

  // Return a new function that uses the original locator but remaps certain functions.
  return (locatorArgs) => {
    const locator = l(locatorArgs);

    locator.click = async (args) => {
      await new Promise((r) => setTimeout(r, waitInMs));
      return l(locatorArgs).click(args);
    };

    locator.fill = async (args) => {
      await new Promise((r) => setTimeout(r, waitInMs));
      return l(locatorArgs).fill(args);
    };

    return locator;
  };
}

/**
 * The extractPath function.
 *
 * This function extracts the path (e.g. /path/to/page)
 * from the current page URL.
 *
 * @param page
 *   Page object from Playwright.
 */
const extractPath = async (page: Page) => {
  const fullUrl = page.url();
  return new URL(fullUrl).pathname;
}

export {
  slowLocator,
  extractPath,
};


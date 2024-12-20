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

/**
 * The hideSlidePopup function.
 *
 * This function hides the sliding popup (cookie consent)
 * banner by clicking the "Agree" button on it.
 *
 * @param page
 *  Playwright page object
 */
const hideSlidePopup = async (page: Page) => {
  try {
    const slidingPopup = await page.locator('#sliding-popup');
    const agreeButton = await page.locator('.agree-button.eu-cookie-compliance-default-button');

    if (!slidingPopup || !agreeButton) {
      logger('Sliding popup already closed for this session.');
      return;
    }

    await Promise.all([
      slidingPopup.waitFor({state: 'visible', timeout: 1000}),
      agreeButton.waitFor({state: 'visible', timeout: 1000}),
      agreeButton.click(),
    ]).then(async () => {
      logger('Closed sliding popup.')
    });
  }
  catch (error) {
    logger('Sliding popup already closed for this session.')
  }
}

/**
 * The getApplicationNumberFromBreadCrumb function.
 *
 * This functions fetches an applications ID from
 * the active breadcrumbs and returns the ID.
 *
 * @param page
 *  Playwright page object.
 */
const getApplicationNumberFromBreadCrumb = async (page: Page) => {
  const breadcrumbSelector = '.breadcrumb__link';
  const breadcrumbLinks = await page.$$(breadcrumbSelector);
  return await breadcrumbLinks[breadcrumbLinks.length - 1].textContent();
}

/**
 * The waitForTextWithInterval function.
 *
 * This function waits for text to be visible on the page,
 * retrying every interval until a timeout. Can be used to implement soft
 * assertion-like behavior by not throwing an error if it fails.
 *
 * @param page
 *   The Playwright page object.
 * @param text
 *   The text to look for on the page.
 * @param timeout
 *   Maximum time in milliseconds to retry.
 * @param interval
 *   Time in milliseconds between retries.
 */
const waitForTextWithInterval = async (
  page: Page,
  text: string,
  timeout?: number,
  interval?: number,
): Promise<boolean> => {
  logger(`Attempting to locate text: "${text}"...`);

  // Default values for timeouts.
  const DEFAULT_TIMEOUT = 60000;
  const DEFAULT_INTERVAL = 5000;

  // Read from environment variables or use defaults.
  const envTimeout = process.env.WAIT_FOR_TEXT_TIMEOUT ? parseInt(process.env.WAIT_FOR_TEXT_TIMEOUT) : DEFAULT_TIMEOUT;
  const envInterval = process.env.WAIT_FOR_TEXT_INTERVAL ? parseInt(process.env.WAIT_FOR_TEXT_INTERVAL) : DEFAULT_INTERVAL;

  // Check for passed in values.
  timeout = timeout ?? envTimeout;
  interval = interval ?? envInterval;

  // Check if the environment variables are correctly set, otherwise use defaults.
  if (timeout < interval) {
    logger(`Environment variables invalid. Using default values: Timeout ${DEFAULT_TIMEOUT}ms, Interval ${DEFAULT_INTERVAL}ms`);
    timeout = DEFAULT_TIMEOUT;
    interval = DEFAULT_INTERVAL;
  }

  const startTime = Date.now();

  while (true) {
    try {
      await page.getByText(text, {exact: true}).waitFor({state: 'visible', timeout : interval})
      logger('Text found!');
      return true;
    } catch (error) {
      const currentTime = Date.now();
      if ((currentTime - startTime) > timeout) {
        logger(`Failed to find text. Timeout: ${timeout}ms. Interval: ${interval}ms.`);
        return false;
      }
      logger('Timeout passed. Retrying...');
    }
  }
}

/**
 * The getFulfilledResponse function.
 *
 * Wait for a fulfilled response from a request.
 *
 * @param page
 *  Playwright page object
 */
async function getFulfilledResponse(page: Page) {
  const response = await page.waitForResponse(async (response) => {
    return response.ok();
  });

  return response.json();
}

/**
 * The logCurrentUrl function.
 *
 * This functions logs the current URL of the page.
 *
 * @param page
 *  Playwright page object.
 */
const logCurrentUrl = async (page: Page) => {
  logger('On URL:', page.url());
}

export {
  extractPath,
  getApplicationNumberFromBreadCrumb,
  getFulfilledResponse,
  hideSlidePopup,
  logCurrentUrl,
  slowLocator,
  waitForTextWithInterval,
};


import {Locator, Page} from "@playwright/test";
import {logger} from "./logger";

declare global {
  interface Window {
    hds?: {
      cookieConsent?: {
        setGroupsStatusToAccepted: (categories: string[]) => boolean;
      };
    };
  }
}

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
 * The acceptCookies function.
 *
 * This function accepts the site-wide cookies.
 *
 * @param page
 *   Playwright page object
 */
const acceptCookies = async (page: Page) => {
  try {
    // Wait for the cookie banner to be attached to the DOM
    await page.waitForSelector('.hds-cc--banner', { state: 'attached', timeout: 3000 });

    // Wait until the button is available before clicking
    const agreeButton = page.locator('.hds-cc__all-cookies-button');
    await agreeButton.waitFor({ state: 'attached', timeout: 1000 });
    await agreeButton.click();
    logger('Accepted cookies.');
  } catch (error) {
    logger('No cookie banner found or already accepted.')
  }
};

/**
 * The hideDialog function.
 *
 * This function hides survey dialog.
 *
 * @param page
 *   Playwright page object
 */
const hideDialog = async (page: Page) => {
  try {
    // Wait for the cookie banner to be attached to the DOM
    await page.waitForSelector('.dialog__container', { state: 'attached', timeout: 3000 });

    // Wait until the button is available before clicking
    const skipButton = page.locator('#helfi-survey__close-button');
    await skipButton.waitFor({ state: 'attached', timeout: 1000 });
    await skipButton.click();
  } catch (error) {
  }
};

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
  const lastDesktopBreadcrumbLink = await page.$('.hds-breadcrumb__list--desktop .hds-breadcrumb__list-item:last-child .hds-breadcrumb__link');
  return lastDesktopBreadcrumbLink?.textContent();
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
 * Make sure the fulfilled response is not from
 * cookie banner or cookie monster.
 *
 * @param page
 *  Playwright page object
 */
async function getFulfilledResponse(page: Page) {
  const response = await page.waitForResponse(async (response) => {
    return response.ok() && !response.url().includes('api/cookie-banner');
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
  acceptCookies,
  extractPath,
  getApplicationNumberFromBreadCrumb,
  getFulfilledResponse,
  hideDialog,
  logCurrentUrl,
  slowLocator,
  waitForTextWithInterval,
};

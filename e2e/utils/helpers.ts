import {faker} from "@faker-js/faker";
import {expect, Locator, Page} from "@playwright/test";
import {logger} from "./logger";
import fs from 'fs';
import path from 'path';

const PATH_TO_TEST_PDF = path.join(__dirname, './data/attachments/test.pdf');
const PATH_TO_TEST_EXCEL = path.join(__dirname, './data/attachments/test.xlsx');

/**
 * Return a "slow" page locator that waits before 'click' and 'fill' requests.
 *
 * @param page
 * @param waitInMs
 */
function slowLocator(
    page: Page,
    waitInMs: number
): (...args: any[]) => Locator {
    // Grab original
    const l = page.locator.bind(page);

    // Return a new function that uses the original locator but remaps certain functions
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


const startNewApplication = async (page: Page, applicationName: string) => {
    await page.goto('fi/etsi-avustusta')

    const searchInput = page.locator("#edit-search");
    const searchButton = page.locator("#edit-submit-application-search-search-api");

    await searchInput.fill(applicationName);
    await searchButton.click();

    const linkToApplication = page.getByRole('link', {name: applicationName});
    await expect(linkToApplication).toBeVisible()
    await linkToApplication.click();

    await page.locator('a[href*="uusi-hakemus"]').first().click();
}

const checkErrorNofification = async (page: Page) => {
    const errorNotificationVisible = await page.locator("form .hds-notification--error").isVisible();
    let errorText = "";

    if (errorNotificationVisible) {
        errorText = await page.locator("form .hds-notification--error").textContent() ?? "Application preview page contains errors";
    }

    expect(errorNotificationVisible, errorText).toBeFalsy();
}

const acceptCookies = async (page: Page) => {
    // const acceptCookiesButton = page.getByRole('button', {name: 'Hyväksy vain välttämättömät evästeet'});
    const acceptCookiesButton = page.locator('.eu-cookie-compliance-save-preferences-button')

    acceptCookiesButton.waitFor(
        {state: "visible", timeout: 500})
        .then(async () => await acceptCookiesButton.click())

}

const clickContinueButton = async (page: Page) => {
    const continueButton = page.getByRole('button', {name: 'Seuraava'});
    await continueButton.click();
}

const clickGoToPreviewButton = async (page: Page) => {
    const goToPreviewButton = page.getByRole('button', {name: 'Esikatseluun'});
    await goToPreviewButton.click();
}

const saveAsDraft = async (page: Page) => {
    const saveAsDraftButton = page.getByRole('button', {name: 'Tallenna keskeneräisenä'});
    await saveAsDraftButton.click();
}

const uploadFile = async (page: Page, selector: string, filePath: string = PATH_TO_TEST_PDF) => {
    const fileInput = page.locator(selector);
    const responsePromise = page.waitForResponse(r => r.request().method() === "POST", {timeout: 30 * 1000});

    // FIXME: Use locator actions and web assertions that wait automatically
    await page.waitForTimeout(2000);

    await expect(fileInput).toBeAttached();
    await fileInput.setInputFiles(filePath);

    await page.waitForTimeout(2000);

    await expect(fileInput, "File upload failed").toBeHidden();
    await responsePromise;
}

const uploadBankConfirmationFile = async (page: Page, selector: string) => {
    const fileInput = page.locator(selector);
    const fileLink = page.locator(".form-item-bankaccountwrapper-0-bank-confirmationfile a")
    const responsePromise = page.waitForResponse(r => r.request().method() === "POST", {timeout: 15 * 1000})

    // FIXME: Use locator actions and web assertions that wait automatically
    await page.waitForTimeout(2000);

    await expect(fileInput).toBeAttached();
    await fileInput.setInputFiles(PATH_TO_TEST_PDF)

    await page.waitForTimeout(2000);

    await responsePromise;
    await expect(fileLink).toBeVisible()
}

const setupUnregisteredCommunity = async (page: Page) => {
    const communityName = faker.lorem.word()
    const personName = faker.person.fullName()
    const email = faker.internet.email()
    const phoneNumber = faker.phone.number()

    await page.goto('/fi/asiointirooli-valtuutus')

    await page.locator('#edit-unregistered-community-selection').selectOption('new');
    await page.getByRole('button', {name: 'Lisää uusi Rekisteröitymätön yhteisö tai ryhmä'}).click();
    await page.getByRole('textbox', {name: 'Yhteisön tai ryhmän nimi'}).fill(communityName);
    await page.getByLabel('Suomalainen tilinumero IBAN-muodossa').fill(process.env.TEST_USER_IBAN ?? '');
    await uploadBankConfirmationFile(page, '[name="files[bankAccountWrapper_0_bank_confirmationFile]"]')

    await page.getByLabel('Nimi', {exact: true}).fill(personName);
    await page.getByLabel('Sähköpostiosoite').fill(email);
    await page.getByLabel('Puhelinnumero').fill(phoneNumber);

    // Submit
    await page.getByRole('button', {name: 'Tallenna omat tiedot'}).click();
    await expect(page.getByText('Profiilitietosi on tallennettu')).toBeVisible()
}

const getKeyValue = (key: string) => {
    const envValue = process.env[key];
    if (envValue) {
        return envValue;
    }

    const pathToLocalSettings = path.join(__dirname, '../../public/sites/default/local.settings.php');
    try {
        const localSettingsContents = fs.readFileSync(pathToLocalSettings, 'utf8');

        const regex = new RegExp(`putenv\\('${key}=(.*?)'\\)`);
        const matches = localSettingsContents.match(regex);
        if (matches && matches.length > 1) {
          return matches[1];
        } else {
            logger(`Could not parse ${key} from configuration file.`);
        }
    } catch (error) {
        logger(`Error reading ${pathToLocalSettings}: ${error}`);
    }

    return '';
};

/**
 * Save object to process.env
 * @param variableName
 * @param data
 */
function saveObjectToEnv(variableName: string, data: Object) {
    let existingObject = {};

    let existingBaseData = process.env.storedData;
    let existingEncoded = {};

    if (existingBaseData) {
        try {
            existingEncoded = JSON.parse(existingBaseData);

            if (typeof existingEncoded === 'object' && existingEncoded !== null) {
                // @ts-ignore
                existingObject = existingEncoded[variableName] || {};
            } else {
                logger('Existing data is not an object.');
                return;
            }
        } catch (error) {
            logger('Error parsing existing data:', error);
            return;
        }
    }

    if (typeof data === 'object') {
        const merged = {
            ...existingObject,
            ...data,
        };

        if (typeof existingEncoded === 'object') {
            // @ts-ignore
            existingEncoded[variableName] = merged;
        }

        //logger('SAVETO', existingEncoded)

        process.env.storedData = JSON.stringify(existingEncoded);
    } else {
        logger('Data must be an object.');
    }
}

/**
 * Get stored data
 *
 * @param profileType
 * @param formId
 * @param full
 */
function getObjectFromEnv(profileType: string, formId: string, full: boolean = false) {
    const storeName = `${profileType}_${formId}`;

    const existingBaseData = process.env.storedData;

    if (existingBaseData) {
        try {
            const existingEncoded = JSON.parse(existingBaseData);
            if (existingEncoded) {
                if (full) {
                    return existingEncoded;
                }
                return existingEncoded[storeName];
            }

        } catch (error) {
            logger('Error parsing existing data:', error);
            return;
        }
    }
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

export {
  PATH_TO_TEST_PDF,
  PATH_TO_TEST_EXCEL,
  acceptCookies,
  checkErrorNofification,
  clickContinueButton,
  clickGoToPreviewButton,
  getKeyValue,
  saveAsDraft,
  setupUnregisteredCommunity,
  startNewApplication,
  uploadBankConfirmationFile,
  uploadFile,
  slowLocator,
  saveObjectToEnv,
  extractPath,
  getObjectFromEnv,
  hideSlidePopup,
  getApplicationNumberFromBreadCrumb,
};


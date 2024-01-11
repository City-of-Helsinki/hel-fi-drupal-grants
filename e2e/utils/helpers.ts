import {faker} from "@faker-js/faker";
import {expect, Locator, Page} from "@playwright/test";
import {logger} from "./logger";
import fs from 'fs';
import path from 'path';
import {TEST_IBAN} from "./data/test_data";


const PATH_TO_TEST_PDF = path.join(__dirname, './data/test.pdf');
const PATH_TO_TEST_EXCEL = path.join(__dirname, './data/test.xlsx');


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
    await page.getByLabel('Suomalainen tilinumero IBAN-muodossa').fill(TEST_IBAN);
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
            const value = matches[1];

            logger('ENV VALUE', key, value);

            return value;
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

        logger('SAVETO', existingEncoded)

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

const extractUrl = async (page: Page) => {
// Get the entire URL
    const fullUrl = page.url();
    logger('Full URL:', fullUrl);

    // Get the path (e.g., /path/to/page)
    const path = new URL(fullUrl).pathname;
    logger('Path:', path);

    return path;
}

const bankAccountConfirmationPath = path.join(__dirname, './data/test.pdf');

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
    extractUrl,
    getObjectFromEnv,
    bankAccountConfirmationPath
};


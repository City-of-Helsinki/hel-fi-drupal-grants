import { faker } from "@faker-js/faker";
import { Page, expect } from "@playwright/test";
import fs from 'fs';
import path from 'path';
import { TEST_IBAN, TEST_SSN } from "./test_data";

type Role = "REGISTERED_COMMUNITY" | "UNREGISTERED_COMMUNITY" | "PRIVATE_PERSON"


const AUTH_FILE_PATH = '.auth/user.json';
const PATH_TO_TEST_PDF = path.join(__dirname, './test.pdf');
const PATH_TO_TEST_EXCEL = path.join(__dirname, './test.xlsx');


const login = async (page: Page, SSN?: string) => {
    await page.goto('/fi/user/login');
    await page.locator("#edit-openid-connect-client-tunnistamo-login").click();
    await page.locator("#fakevetuma2").click()
    await page.locator("#hetu_input").fill(SSN ?? TEST_SSN);
    await page.locator('.box').click()
    await page.locator('#tunnistaudu').click();
    await page.locator('#continue-button').click();
    await page.waitForSelector('text="Helsingin kaupunki"');
}

const loginWithCompanyRole = async (page: Page, SSN?: string) => {
    await login(page, SSN);
    await selectRole(page, 'REGISTERED_COMMUNITY')
}

const loginAsPrivatePerson = async (page: Page, SSN?: string) => {
    await login(page, SSN);
    await selectRole(page, 'PRIVATE_PERSON')
}

const selectRole = async (page: Page, role: Role) => {
    await page.goto("/fi/asiointirooli-valtuutus");

    const pageUnavailable = await page.getByText("Sinulla ei ole käyttöoikeutta tälle sivulle").isVisible()

    if (pageUnavailable) {
        page.context().clearCookies()
        await loginAndSaveStorageState(page)
    }

    const loggedInAsCompanyUser = await page.locator(".asiointirooli-block-registered_community").isVisible()
    const loggedAsPrivatePerson = await page.locator(".asiointirooli-block-private_person").isVisible()
    const loggedInAsUnregisteredCommunity = await page.locator(".asiointirooli-block-unregistered_community").isVisible()

    if (role === 'REGISTERED_COMMUNITY' && !loggedInAsCompanyUser) {
        await selectRegisteredCommunityRole(page);
    }

    if (role === 'UNREGISTERED_COMMUNITY' && !loggedInAsUnregisteredCommunity) {
        await selectUnregisteredCommunityRole(page);
    }

    if (role === 'PRIVATE_PERSON' && !loggedAsPrivatePerson) {
        await selectPrivatePersonRole(page);
    }

}

const selectRegisteredCommunityRole = async (page: Page) => {
    const registeredCommunityButton = page.locator('[name="registered_community"]')
    await expect(registeredCommunityButton).toBeVisible()
    await registeredCommunityButton.click()
    const firstCompanyRow = page.locator('input[type="radio"]').first()
    await firstCompanyRow.check({ force: true })
    await page.locator('[data-test="perform-confirm"]').click()
}

const selectUnregisteredCommunityRole = async (page: Page) => {
    await page.locator('#edit-unregistered-community-selection').selectOption({ index: 2 });
    await page.locator('[name="unregistered_community"]').click()
}

const selectPrivatePersonRole = async (page: Page) => {
    await page.locator('[name="private_person"]').click()
}

const startNewApplication = async (page: Page, applicationName: string) => {
    await page.goto('fi/etsi-avustusta')

    const searchInput = page.locator("#edit-search");
    const searchButton = page.locator("#edit-submit-application-search-search-api");

    await searchInput.fill(applicationName);
    await searchButton.click();

    const linkToApplication = page.getByRole('link', { name: applicationName });
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
    const acceptCookiesButton = page.getByRole('button', { name: 'Hyväksy vain välttämättömät evästeet' });
    await acceptCookiesButton.click();
}

const clickContinueButton = async (page: Page) => {
    const continueButton = page.getByRole('button', { name: 'Seuraava' });
    await continueButton.click();
}

const clickGoToPreviewButton = async (page: Page) => {
    const goToPreviewButton = page.getByRole('button', { name: 'Esikatseluun' });
    await goToPreviewButton.click();
}

const saveAsDraft = async (page: Page) => {
    const saveAsDraftButton = page.getByRole('button', { name: 'Tallenna keskeneräisenä' });
    await saveAsDraftButton.click();
}

const loginAndSaveStorageState = async (page: Page) => {
    await login(page);
    await page.context().storageState({ path: AUTH_FILE_PATH });
}

const uploadFile = async (page: Page, selector: string, filePath: string = PATH_TO_TEST_PDF) => {
    const fileInput = page.locator(selector);
    const responsePromise = page.waitForResponse(r => r.request().method() === "POST", { timeout: 30 * 1000 });

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
    const responsePromise = page.waitForResponse(r => r.request().method() === "POST", { timeout: 15 * 1000 })

    // FIXME: Use locator actions and web assertions that wait automatically
    await page.waitForTimeout(1000);

    await expect(fileInput).toBeAttached();
    await fileInput.setInputFiles(PATH_TO_TEST_PDF)

    await page.waitForTimeout(1000);

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
    await page.getByRole('button', { name: 'Lisää uusi Rekisteröitymätön yhteisö tai ryhmä' }).click();
    await page.getByRole('textbox', { name: 'Yhteisön tai ryhmän nimi' }).fill(communityName);
    await page.getByLabel('Suomalainen tilinumero IBAN-muodossa').fill(TEST_IBAN);
    await uploadBankConfirmationFile(page, '[name="files[bankAccountWrapper_0_bank_confirmationFile]"]')

    await page.getByLabel('Nimi', { exact: true }).fill(personName);
    await page.getByLabel('Sähköpostiosoite').fill(email);
    await page.getByLabel('Puhelinnumero').fill(phoneNumber);

    // Submit
    await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
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
            return value;
        } else {
            console.error(`Could not parse ${key} from configuration file.`);
        }
    } catch (error) {
        console.error(`Error reading ${pathToLocalSettings}: ${error}`);
    }

    return '';
};

export {
    AUTH_FILE_PATH,
    PATH_TO_TEST_PDF,
    PATH_TO_TEST_EXCEL,
    acceptCookies,
    checkErrorNofification,
    clickContinueButton,
    clickGoToPreviewButton,
    getKeyValue,
    login,
    loginAndSaveStorageState,
    loginAsPrivatePerson,
    loginWithCompanyRole,
    saveAsDraft,
    selectRole,
    setupUnregisteredCommunity,
    startNewApplication,
    uploadBankConfirmationFile,
    uploadFile
};


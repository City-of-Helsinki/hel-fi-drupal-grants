import { faker } from "@faker-js/faker";
import { Locator, Page, expect } from "@playwright/test";
import path from 'path';
import { TEST_IBAN, TEST_SSN } from "./test_data";

type Role = "REGISTERED_COMMUNITY" | "UNREGISTERED_COMMUNITY" | "PRIVATE_PERSON"


const AUTH_FILE_PATH = '.auth/user.json';


const login = async (page: Page, SSN?: string) => {
    await page.goto('/fi/user/login');
    await page.locator("#edit-openid-connect-client-tunnistamo-login").click();
    await page.locator("#fakevetuma2").click()
    await page.locator("#hetu_input").fill(SSN || TEST_SSN);
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

    switch (role) {
        case 'REGISTERED_COMMUNITY':
            if (loggedInAsCompanyUser) return;
            const registeredCommunityButton = page.locator('[name="registered_community"]')
            await expect(registeredCommunityButton).toBeVisible()
            await registeredCommunityButton.click()
            const firstCompanyRow = page.locator('input[type="radio"]').first()
            await firstCompanyRow.check({ force: true })
            await page.locator('[data-test="perform-confirm"]').click()
            break;

        case "UNREGISTERED_COMMUNITY":
            if (loggedInAsUnregisteredCommunity) return;
            await page.locator('#edit-unregistered-community-selection').selectOption({ index: 2 });
            await page.locator('[name="unregistered_community"]').click()
            break

        case "PRIVATE_PERSON":
            if (loggedAsPrivatePerson) return;
            await page.locator('[name="private_person"]').click()
            break

        default:
            break;
    }
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

const expectRequiredAttribute = async (locators: Locator[]) => {
    locators.forEach(async locator => {
        const requiredAttribute = await locator.getAttribute("required");
        expect(requiredAttribute, `${locator} should contain a required attribute`).toBeTruthy()
    });
}

const uploadBankConfirmationFile = async (page: Page, selector: string) => {
    const fileInput = page.locator(selector);
    const fileLink = page.locator(".form-item-bankaccountwrapper-0-bank-confirmationfile a")
    const responsePromise = page.waitForResponse(r => r.request().method() === "POST", { timeout: 15 * 1000 })

    // TODO: Use locator actions and web assertions that wait automatically
    await page.waitForTimeout(1000);

    await expect(fileInput).toBeAttached();
    await fileInput.setInputFiles(path.join(__dirname, './test.pdf'))

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

export {
    AUTH_FILE_PATH, acceptCookies,
    clickContinueButton,
    clickGoToPreviewButton,
    expectRequiredAttribute,
    login,
    loginAndSaveStorageState,
    loginAsPrivatePerson,
    loginWithCompanyRole,
    saveAsDraft,
    selectRole,
    setupUnregisteredCommunity,
    startNewApplication, uploadBankConfirmationFile
};


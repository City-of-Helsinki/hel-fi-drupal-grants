import { Page, expect } from "@playwright/test";
import { TEST_SSN } from "./test_data";

type Role = "REGISTERED_COMMUNITY" | "UNREGISTERED_COMMUNITY" | "PRIVATE_PERSON"


const AUTH_FILE = '.auth/user.json';


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

    // TODO: Temporary solution, waiting for AU-1714
    const loggedInAsCompanyUser = await page.getByText("Lachael Testifirma OY").isVisible()
    const loggedAsPrivatePerson = await page.locator(".page--oma-asiointi__private-person").isVisible()
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
            // TODO: Handle case if no communities to select
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
    await continueButton.scrollIntoViewIfNeeded();
    await continueButton.click();
}


const loginAndSaveStorageState = async (page: Page) => {
    await login(page);
    await page.context().storageState({ path: AUTH_FILE });
}


export { AUTH_FILE, acceptCookies, login, loginWithCompanyRole, loginAsPrivatePerson, startNewApplication, selectRole, clickContinueButton, loginAndSaveStorageState }
import { Page, expect } from "@playwright/test";
import { TEST_SSN } from "./test_data";

const login = async (page: Page, SSN?: string) => {
    await page.goto('/');
    await page.getByRole('link', { name: 'Kirjaudu' }).click();
    await page.getByRole('button', { name: 'Kirjaudu sisään' }).click();
    await page.locator("#fakevetuma2").click()
    await page.locator("#hetu_input").fill(SSN || TEST_SSN);
    await page.locator('.box').click()
    await page.locator('#tunnistaudu').click();
    await page.locator('#continue-button').click();
}

const loginWithCompanyRole = async (page: Page, SSN?: string) => {
    await login(page, SSN);
    await page.locator('[name="registered_community"]').click()
    const firstCompanyRow = page.locator('input[type="radio"]').first()
    await firstCompanyRow.check({force: true})
    await page.locator('[data-test="perform-confirm"]').click()
}

const loginAsPrivatePerson = async (page: Page, SSN?: string) => {
    await login(page, SSN);
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

const acceptCookies = async (page: Page) => {
    const acceptCookiesButton = page.getByRole('button', { name: 'Hyväksy vain välttämättömät evästeet' });
    await acceptCookiesButton.click();
}


export { acceptCookies, login, loginWithCompanyRole, loginAsPrivatePerson, startNewApplication }
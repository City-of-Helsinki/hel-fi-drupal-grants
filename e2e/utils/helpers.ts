import { Page } from "@playwright/test";
import { TEST_SSN } from "./test_data";

const login = async (page: Page, SSN?: string) => {
    await page.goto('/');
    await page.getByRole('link', { name: 'Kirjaudu' }).click();
    await page.getByRole('button', { name: 'Kirjaudu sisään' }).click();
    await page.getByRole('link', { name: 'Test IdP' }).click();
    await page.getByPlaceholder('210281-9988').fill(SSN || TEST_SSN);
    await page.locator('.box').click()
    await page.getByRole('button', { name: 'Tunnistaudu' }).click();
    await page.getByRole('button', { name: 'Jatka palveluun' }).click();

}

const loginWithCompanyRole = async (page: Page, SSN?: string) => {
    await login(page, SSN);
    await page.getByRole('button', { name: 'Valitse rooli Rekisteröity yhteisö ja tee valtuutus'}).click();
    await page.getByText('Lachael Testifirma OY').click();
    await page.locator('[data-test="perform-confirm"]').click()
}

const loginAsPrivatePerson = async (page: Page, SSN?: string) => {
    await login(page, SSN);
    await page.getByRole('button', { name: 'Valitse rooli Yksityishenkilö' }).click();
}

const startNewApplication = async (page: Page, applicationName: string) => {
    await page.goto('fi/etsi-avustusta')
    await page.getByPlaceholder('Etsi nimellä tai hakusanalla, esim toiminta-avustus').fill(applicationName);
    await page.getByRole('button', { name: 'Etsi' }).click();
    await page.getByRole('link', { name: applicationName }).click();
    await page.locator('#block-servicepageauthblock').getByRole('link', { name: 'Uusi hakemus' }).click();   //TODO
}

const acceptCookies = async (page: Page) => {
    const acceptCookiesButton = page.getByRole('button', { name: 'Hyväksy vain välttämättömät evästeet' });
    await acceptCookiesButton.click();
}


export { acceptCookies, login, loginWithCompanyRole, loginAsPrivatePerson, startNewApplication }
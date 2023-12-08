import { Page, expect, test } from '@playwright/test';

test.describe("Frontpage", () => {
    let page: Page;

    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage()
    });

    test.beforeEach(async () => {
        await page.goto('/');
    });

    test('check nav bar dropdown links', async () => {
        const linkNames = [
            'Ajankohtaista avustuksista',
            'Kulttuurin avustukset',
            'Liikunnan avustukset',
            'Nuorisotoiminnan avustukset',
            'Asukasosallisuuden avustukset'
        ];

        // Check the "Tietoa avustuksista" dropdown
        for (const name of linkNames) await expect(page.getByRole('link', { name })).toBeHidden();
        await page.getByLabel('Tietoa avustuksista').click();
        for (const name of linkNames) await expect(page.getByRole('link', { name })).toBeVisible();

        // Check the "Ohjeita hakijalle" dropdown
        await expect(page.getByRole('link', { name: "Palvelun käyttöohjeet" })).toBeHidden()
        await page.getByLabel('Ohjeita hakijalle').click();
        await expect(page.getByRole('link', { name: "Palvelun käyttöohjeet" })).toBeVisible()
    });

    test('can change language', async () => {
        await page.getByRole('link', { name: 'Svenska' }).click();
        await expect(page.getByRole('heading', { name: 'Understöd', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Latest news' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Du är kanske intresserad av' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Ta kontakt' })).toBeVisible();

        await page.getByRole('link', { name: 'English' }).click();
        await expect(page.getByRole('heading', { name: 'Grants', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Latest news' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'You may also be interested in' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'City of Helsinki' })).toBeVisible();

        await page.getByRole('link', { name: 'Suomi' }).click();
        await expect(page.getByRole('heading', { name: 'Avustukset' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Ajankohtaista avustuksista' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Sinua voisi kiinnostaa' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Helsingin kaupunki' })).toBeVisible();
    });


    test('has nav bar links', async () => {
        const headerText = await page.locator('header').textContent();
        expect(headerText).toContain('Tietoa avustuksista');
        expect(headerText).toContain('Etsi avustusta');
        expect(headerText).toContain('Ohjeita hakijalle');
    });


    test('has footer links', async () => {
        await expect(page.getByRole('link', { name: 'Avoimet työpaikat' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Sosiaalinen media' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Medialle' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Ota yhteyttä kaupunkiin' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Yleisneuvontaa palveluista: Helsinki-info' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Digituki' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Anna palautetta' })).toBeVisible();

        await expect(page.getByRole('link', { name: 'Saavutettavuusseloste' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Tietopyynnöt' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Tietoa hel.fistä' })).toBeVisible();
    });


    test('has cookie banner', async () => {
        await expect(page.getByText('Hel.fi käyttää evästeitä Tämä sivusto käyttää välttämättömiä evästeitä')).toBeVisible()
        await expect(page.getByRole('button', { name: 'Näytä evästeet' })).toBeEnabled()
        await expect(page.getByRole('button', { name: 'Hyväksy kaikki evästeet' })).toBeEnabled()
        await expect(page.getByRole('button', { name: 'Hyväksy vain välttämättömät evästeet' })).toBeEnabled()
    });
        
    test.afterAll(async () => {
        await page.close();
    });
});

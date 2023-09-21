import { expect, test } from '@playwright/test';
import { BASE_URL } from '../test_data';

test.beforeEach(async ({ page }) => {
    await page.goto(BASE_URL);
});


test('check nav bar dropdown links', async ({ page }) => {
    const linkNames = [
        'Ajankohtaista avustuksista',
        'Kulttuurin avustukset',
        'Liikunnan avustukset',
        'Nuorisotoiminnan avustukset',
        'Asukasosallisuuden avustukset'
    ];

    // Check the "Tietoa avustuksista" dropdown
    for (const name of linkNames) await expect(page.getByRole('link', { name })).not.toBeVisible();
    await page.getByLabel('Tietoa avustuksista').click();
    for (const name of linkNames) await expect(page.getByRole('link', { name })).toBeVisible();


    // Check the "Ohjeita hakijalle" dropdown
    await expect(page.getByRole('link', { name: "Palvelun käyttöohjeet" })).not.toBeVisible()
    await page.getByLabel('Ohjeita hakijalle').click();
    await expect(page.getByRole('link', { name: "Palvelun käyttöohjeet" })).toBeVisible()
});

test('can change language', async ({ page }) => {
    await page.getByRole('link', { name: 'Svenska' }).click();
    await expect(page.getByRole('heading', { name: 'Understöd', exact: true })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'På dessa sidor' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Behöver du hjälp med ansökan?' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Latest news' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Du är kanske intresserad av' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Ta kontakt' })).toBeVisible();

    await page.getByRole('link', { name: 'English' }).click();

    await expect(page.getByText('City of Helsinki grants for organisations, communities, groups of residents and ')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'On these pages' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Latest news' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Do you need help filling out your application?' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'You may also be interested in' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'City of Helsinki' })).toBeVisible();

    await page.getByRole('link', { name: 'Suomi' }).click();

    await expect(page.getByRole('heading', { name: 'Avustukset' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Näillä sivuilla' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Pääset täyttämään hakemusta kirjautumalla omaan asiointiin ja luomalla hakijaprofiilin.' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Ajankohtaista avustuksista' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Tarvitsetko apua hakemuksen tekemiseen?' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Sinua voisi kiinnostaa' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Helsingin kaupunki' })).toBeVisible();
});


test('has nav bar links', async ({ page }) => {
    const headerText = await page.locator('header').textContent();
    expect(headerText).toContain('Tietoa avustuksista');
    expect(headerText).toContain('Etsi avustusta');
    expect(headerText).toContain('Ohjeita hakijalle');
});


test('has footer links', async ({ page }) => {
    const expectedTexts = [
        'Avoimet työpaikat',
        'Sosiaalinen media',
        'Medialle',
        'Ota yhteyttä kaupunkiin',
        'Saavutettavuusseloste',
        'Takaisin ylös',
        'Tietopyynnöt',
        'Digituki',
        'Anna palautetta'
    ];

    const footerText = await page.textContent('footer');

    expectedTexts.forEach(txt => {
        expect(footerText).toContain(txt);
    });
});


test('has cookie banner', async ({ page }) => {
    const cookieBannerText = page.getByText('Hel.fi käyttää evästeitä Tämä sivusto käyttää välttämättömiä evästeitä')
    await expect(cookieBannerText).toBeVisible()
    await expect(page.getByRole('button', { name: 'Näytä evästeet' })).toBeEnabled()
    await expect(page.getByRole('button', { name: 'Hyväksy kaikki evästeet' })).toBeEnabled()
    await expect(page.getByRole('button', { name: 'Hyväksy vain välttämättömät evästeet' })).toBeEnabled()

    const acceptCookiesButton = page.getByRole('button', { name: 'Hyväksy vain välttämättömät evästeet' });
    await acceptCookiesButton.click();

    await expect(cookieBannerText).not.toBeVisible()
});

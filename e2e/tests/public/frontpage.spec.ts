import { Page, expect, test } from '@playwright/test';


test.describe("Frontpage", () => {
    let page: Page;

    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage()
    });

    test.beforeEach(async () => {
        await page.goto('/fi/avustukset');
    })

    test('title', async () => {
        const pageTitle = await page.title();
        expect(pageTitle).toContain("Avustusasiointi");
    });

    test('hero', async () => {
        await expect(page.locator('.hero').getByRole('heading', { name: 'Avustukset' })).toBeVisible()
    });

    test('Tällä Sivuilla section', async () => {
        await expect(page.locator(".component--list-of-links").getByRole('link', { name: 'Tietoa avustuksista' })).toBeVisible();
        await expect(page.locator(".component--list-of-links").getByRole('link', { name: 'Etsi avustusta' })).toBeVisible();
        await expect(page.locator(".component--list-of-links").getByRole('link', { name: 'Ohjeita hakijalle' })).toBeVisible();
    });

    test('banner', async () => {
        await expect(page.getByRole('heading', { name: 'Pääset täyttämään hakemusta kirjautumalla omaan asiointiin ja luomalla hakijaprofiilin' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Kirjaudu sisään' })).toBeVisible()
    });

    test('help section', async () => {
        await expect(page.locator(".liftup-with-image").getByRole('heading', { name: 'Tarvitsetko apua hakemuksen tekemiseen?' })).toBeVisible();
        await expect(page.locator(".liftup-with-image").getByRole('link', { name: 'Ohjeita hakijalle' })).toBeVisible()
        await expect(page.locator(".liftup-with-image").getByRole('link', { name: 'Tietoa avustuksista' })).toBeVisible()
    });

    test('login button', async () => {
        const loginLink = page.getByRole('link', { name: 'Kirjaudu' })
        await expect(loginLink).toBeVisible()
    });

    test('news section', async () => {
        const newsBlockHeader = page.getByRole('heading', { name: 'Ajankohtaista avustuksista' })
        const linkToNewsPage = page.getByRole('link', { name: 'Katso kaikki ajankohtaiset' })
        const amountOfNewsListings = await page.locator('.news-listing__item').count();

        await expect(newsBlockHeader).toBeVisible()
        expect(amountOfNewsListings).toBeTruthy()
        await expect(linkToNewsPage).toBeVisible()
    })

    test('Sinua Voisi Kiinnostaa section', async () => {
        await expect(page.getByRole('heading', { name: 'Sinua voisi kiinnostaa' })).toBeVisible()
        await expect(page.getByRole('link', { name: 'Yleiset avustusehdot' })).toBeVisible()
        await expect(page.getByRole('link', { name: 'Tilavaraukset' })).toBeVisible()
        await expect(page.getByRole('link', { name: 'Päätökset-palvelu' })).toBeVisible()
    });

    test.afterAll(async () => {
        await page.close();
    });
})

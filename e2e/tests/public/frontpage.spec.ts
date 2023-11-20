import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
    await page.goto('/fi/avustukset');
});


test('verify title', async ({ page }) => {
    await expect(page).toHaveTitle(/.*Avustusasiointi/);
});

test('verify hero', async ({ page }) => {
    await expect(page.locator('.hero').getByRole('heading', { name: 'Avustukset' })).toBeVisible()
});

test('verify Tällä Sivuilla section', async ({ page }) => {
    await expect(page.locator(".component--list-of-links").getByRole('link', { name: 'Tietoa avustuksista' })).toBeVisible();
    await expect(page.locator(".component--list-of-links").getByRole('link', { name: 'Etsi avustusta' })).toBeVisible();
    await expect(page.locator(".component--list-of-links").getByRole('link', { name: 'Ohjeita hakijalle' })).toBeVisible();
});

test('verify banner', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Pääset täyttämään hakemusta kirjautumalla omaan asiointiin ja luomalla hakijaprofiilin' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Kirjaudu sisään' })).toBeVisible()
});

test('verify help section', async ({ page }) => {
    await expect(page.locator(".liftup-with-image").getByRole('heading', { name: 'Tarvitsetko apua hakemuksen tekemiseen?' })).toBeVisible();
    await expect(page.locator(".liftup-with-image").getByRole('link', { name: 'Ohjeita hakijalle' })).toBeVisible()
    await expect(page.locator(".liftup-with-image").getByRole('link', { name: 'Tietoa avustuksista' })).toBeVisible()
});

test('has a login button', async ({ page }) => {
    const loginLink = page.getByRole('link', { name: 'Kirjaudu' })
    await expect(loginLink).toBeVisible()
});

test('contains news', async ({ page }) => {
    const newsBlockHeader = page.getByRole('heading', { name: 'Ajankohtaista avustuksista' })
    const linkToNewsPage = page.getByRole('link', { name: 'Katso kaikki ajankohtaiset' })
    const amountOfNewsListings = await page.locator('.news-listing__item').count();

    await expect(newsBlockHeader).toBeVisible()
    expect(amountOfNewsListings).toBeTruthy()
    await expect(linkToNewsPage).toBeVisible()
})

test('verify Sinua Voisi Kiinnostaa section', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Sinua voisi kiinnostaa' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Yleiset avustusehdot' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Tilavaraukset' })).toBeVisible()    
    await expect(page.getByRole('link', { name: 'Päätökset-palvelu' })).toBeVisible()
});

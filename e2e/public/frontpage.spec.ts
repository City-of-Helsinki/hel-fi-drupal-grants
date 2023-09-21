import { expect, test } from '@playwright/test';


test.beforeEach(async ({ page }) => {
    await page.goto('/');
});


test('has title', async ({ page }) => {
    await expect(page).toHaveTitle(/.*Avustusasiointi/);
});


test('has a login button', async ({ page }) => {
    const loginLink = page.getByRole('link', { name: 'Kirjaudu' })
    await expect(loginLink).toBeVisible()
});




test('contains news', async ({ page }) => {
    const newsBlockHeader = page.getByRole('heading', { name: 'Ajankohtaista avustuksista' })
    await expect(newsBlockHeader).toBeVisible()

    // A news article is visible
    expect(page.locator('.news-listing__item')).toBeTruthy()

    const linkToNewsPage = page.getByRole('link', { name: 'Katso kaikki ajankohtaiset' })
    await expect(linkToNewsPage).toBeVisible()
})


test('has sinua kiinnostaa section', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Sinua voisi kiinnostaa' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Yleiset avustusehdot' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Tilavaraukset' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Päätökset-palvelu (Linkki johtaa ulkoiseen palveluun)' })).toBeVisible()
});
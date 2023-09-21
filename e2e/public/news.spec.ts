import { expect, test } from '@playwright/test';


test.beforeEach(async ({ page }) => {
    await page.goto('/fi/uutiset');
});


test('has title', async ({ page }) => {
    const pageTitle = await page.title()
    expect(pageTitle).toContain('Ajankohtaista avustuksista')
});

test('contains header', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Pääuutiset' })).toBeVisible()
});

test('contains atleast one news article', async ({ page }) => {
    const articleCount = await page.locator('#block-views-block-frontpage-news-main-news').getByRole('listitem').count();
    expect(articleCount).toBeTruthy()
});

test('news article can be opened', async ({ page }) => {
    const firstLink = page.locator('#block-views-block-frontpage-news-main-news').getByRole('listitem').first()
    await firstLink.click()

    await expect(page.locator(".components--news")).toBeVisible()
});
import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
    await page.goto('/fi/tietoa-avustuksista');
});


test('page title', async ({ page }) => {
    await expect(page).toHaveTitle(/.*Tietoa avustuksista/);
});


test('verify hero', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Tietoa avustuksista' })).toBeVisible();
});


test('section headers', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Kasvatus ja koulutus' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Kulttuuri ja vapaa-aika' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Asukasosallisuuden avustukset', exact: true })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Muut Avustukset' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Ajankohtaista avustuksista' })).toBeVisible();
});

import { Page, expect, test } from '@playwright/test';

test.describe("Info page", () => {
    let page: Page;

    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage()
    });

    test.beforeEach(async () => {
        await page.goto('/fi/tietoa-avustuksista');
    });
        
    test('page title', async () => {
        await expect(page).toHaveTitle(/.*Tietoa avustuksista/);
    });
    
    
    test('verify hero', async () => {
        await expect(page.getByRole('heading', { name: 'Tietoa avustuksista' })).toBeVisible();
    });
    
    
    test('section headers', async () => {
        await expect(page.getByRole('heading', { name: 'Kasvatus ja koulutus' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Kulttuuri ja vapaa-aika' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Asukasosallisuuden avustukset', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Muut Avustukset' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Ajankohtaista avustuksista' })).toBeVisible();
    });
        
    test.afterAll(async () => {
        await page.close();
    });
})

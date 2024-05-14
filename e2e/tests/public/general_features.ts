import {Page, expect, test} from '@playwright/test';
import {logger} from "../../utils/logger";

test.describe("General features", () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage();
    await page.goto('/fi/avustukset');
  });

  test.afterAll(async () => {
    await page.close();
  });

  test('Can change language', async () => {
    logger('Validating language changing...');
    let languageCode: string | null;

    await page.getByRole('link', {name: 'Svenska'}).click();
    languageCode = await page.locator('html').getAttribute('lang');
    await expect(page.getByRole('heading', {name: 'Understöd', exact: true})).toBeVisible();
    await expect(languageCode).toBe('sv');

    await page.getByRole('link', {name: 'English'}).click();
    languageCode = await page.locator('html').getAttribute('lang');
    await expect(page.getByRole('heading', {name: 'Grants', exact: true})).toBeVisible();
    await expect(languageCode).toBe('en');

    await page.getByRole('link', {name: 'Suomi'}).click();
    languageCode = await page.locator('html').getAttribute('lang');
    await expect(page.getByRole('heading', {name: 'Avustukset'})).toBeVisible();
    await expect(languageCode).toBe('fi');
    logger('Language changing validated.');
  });

});

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

  test('Login button', async () => {
    logger('Validating login button...');
    await expect(await page.locator('.profile__login-link')).toHaveText('Kirjaudu');
    logger('Login button validated.');
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

  test('Has cookie banner', async () => {
    logger('Validating cookie banner...');
    await expect(page.getByText('Hel.fi käyttää evästeitä Tämä sivusto käyttää välttämättömiä evästeitä')).toBeVisible();
    await expect(page.getByRole('button', {name: 'Näytä evästeet'})).toBeEnabled();
    await expect(page.getByRole('button', {name: 'Hyväksy kaikki evästeet'})).toBeEnabled();
    await expect(page.getByRole('button', {name: 'Hyväksy vain välttämättömät evästeet'})).toBeEnabled();
    logger('Cookie banner validated.');
  });
});

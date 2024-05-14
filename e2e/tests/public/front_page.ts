import {expect, Page, test} from '@playwright/test';
import {pageCollection} from "../../utils/data/public_page_data";
import {validateComponent, validatePageTitle} from "../../utils/public_helpers";
import {logger} from "../../utils/logger";

const scenario = pageCollection['front_page'];

test.describe(`Testing page: ${scenario.url}`, () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage();
    await page.goto(scenario.url);
  });

  test.afterAll(async () => {
    await page.close();
  });

  test(`Validate page title: ${scenario.url}`, async () => {
    test.skip(!scenario.validatePageTitle, 'Skip page title test');
    await validatePageTitle(page);
  });

  test(`Validate components: ${scenario.url}`, async () => {
    for (const component of scenario.components) {
      await validateComponent(page, component);
    }
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


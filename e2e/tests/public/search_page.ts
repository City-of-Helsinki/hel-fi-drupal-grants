import {expect, Page, test} from '@playwright/test';
import {pageCollection} from "../../utils/data/public_page_data";
import {validateComponent, validatePageTitle} from "../../utils/public_helpers";
import {logger} from "../../utils/logger";

const scenario = pageCollection['search_page'];

test.describe(`Testing page: ${scenario.url}`, () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage();
  });

  test.beforeEach(async () => {
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

  test('Search page: Controls are visible', async () => {
    logger('Validating search page controls...');
    await expect(await page.getByRole('heading', {name: 'Rajaa hakua'})).toBeVisible()
    await expect(await page.getByText('Valitse kohderyhmä')).toBeVisible()
    await expect(await page.getByText('Millaiseen toimintaan haet avustusta?')).toBeVisible()
    await expect(await page.getByText('Avustuksen hakija')).toBeVisible()
    await expect(await page.getByText('Tai etsi hakusanalla')).toBeVisible()
    await expect(await page.getByLabel('Näytä vain haettavissa olevat avustukset')).toBeVisible();
    logger('Search page controls validated.');
  });

  test('Search page: Initial results', async () => {
    logger('Validating search page initial results...');
    const resultCount = await page.locator(".application_search--link").count();
    expect(resultCount).toBeTruthy();
    logger('Search page initial results validated.');
  });

  test('Search page: Search for grants', async () => {
    logger('Validating search page search for grants...');
    const inputField = await page.getByPlaceholder('Etsi nimellä tai hakusanalla, esim toiminta-avustus');

    await inputField.fill('avustus');
    await page.getByRole('button', {name: 'Etsi'}).click();

    const resultCount = await page.locator(".application_search--link").count();
    expect(resultCount).toBeGreaterThan(0);
    logger('Search page search for grants validated.');
  });

  test('Search page: Invalid search', async () => {
    logger('Validating search page invalid search...');
    const inputField = await page.getByPlaceholder('Etsi nimellä tai hakusanalla, esim toiminta-avustus');
    await inputField.fill('xxxxxx');

    await page.getByRole('button', {name: 'Etsi'}).click();

    const resultCount = await page.locator(".application_search--link").count();
    expect(resultCount).toBeFalsy();
    logger('Search page invalid search validated.');
  });

});


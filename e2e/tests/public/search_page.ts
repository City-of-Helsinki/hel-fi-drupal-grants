import {expect, Page, test} from '@playwright/test';
import {pageCollection} from "../../utils/data/public_page_data";
import {validateComponent, validatePageTitle} from "../../utils/public_helpers";

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
    await expect(await page.getByRole('heading', {name: 'Rajaa hakua'})).toBeVisible()
    await expect(await page.getByText('Valitse kohderyhmä')).toBeVisible()
    await expect(await page.getByText('Millaiseen toimintaan haet avustusta?')).toBeVisible()
    await expect(await page.getByText('Avustuksen hakija')).toBeVisible()
    await expect(await page.getByText('Tai etsi hakusanalla')).toBeVisible()
    await expect(await page.getByLabel('Näytä vain haettavissa olevat avustukset')).toBeVisible();
  });

  test('Search page: Initial results', async () => {
    const resultCount = await page.locator(".application_search--link").count();
    expect(resultCount).toBeTruthy();
  });

  test('Search page: Search for grants', async () => {
    const inputField = await page.getByPlaceholder('Etsi nimellä tai hakusanalla, esim toiminta-avustus');

    await inputField.fill('avustus');
    await page.getByRole('button', {name: 'Etsi'}).click();

    const resultCount = await page.locator(".application_search--link").count();
    expect(resultCount).toBeGreaterThan(0);
  });

  test('Search page: Invalid search', async () => {
    const inputField = await page.getByPlaceholder('Etsi nimellä tai hakusanalla, esim toiminta-avustus');
    await inputField.fill('xxxxxx');

    await page.getByRole('button', {name: 'Etsi'}).click();

    const resultCount = await page.locator(".application_search--link").count();
    expect(resultCount).toBeFalsy();
  });

});


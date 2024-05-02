import {Page, expect, test} from '@playwright/test'


test.describe("Hakusivu", () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    await page.goto('/fi/etsi-avustusta');
  });

  test.beforeEach(async () => {
    await page.goto('/fi/etsi-avustusta');
  });

  test('has title', async () => {
    const pageTitle = await page.title()
    expect(pageTitle).toContain('Etsi avustusta')
  })

  test('contains header', async () => {
    await expect(page.getByRole('heading', {name: 'Etsi avustusta'})).toBeVisible()
  })

  test('search filter fields are visible', async () => {
    await expect(page.getByRole('heading', {name: 'Rajaa hakua'})).toBeVisible()
    await expect(page.getByText('Kohderyhmä')).toBeVisible()
    await expect(page.getByText('Avustuslaji')).toBeVisible()
    await expect(page.getByText('Avustuksen hakija')).toBeVisible()
    await expect(page.getByText('Hakusana')).toBeVisible()
    await expect(page.getByLabel('Näytä vain haettavissa olevat avustukset')).toBeVisible();
  });

  test('search results are initially visible', async () => {
    const resultCount = await page.locator(".application_search--link").count()
    expect(resultCount).toBeTruthy()
  });

  test('can search for grants', async () => {
    const inputField = page.getByPlaceholder('Etsi nimellä tai hakusanalla, esim toiminta-avustus')
    await inputField.fill('avustus');

    await page.getByRole('button', {name: 'Etsi'}).click();

    // results are visible
    const resultCount = await page.locator(".application_search--link").count()
    expect(resultCount).toBeGreaterThan(0)
  });

  test('invalid search returns no results', async () => {
    const inputField = page.getByPlaceholder('Etsi nimellä tai hakusanalla, esim toiminta-avustus')
    await inputField.fill('xxxxxx');

    await page.getByRole('button', {name: 'Etsi'}).click();

    // no grants should be found
    const resultCount = await page.locator(".application_search--link").count()
    expect(resultCount).toBeFalsy()
  });

  test('search result link can be opened', async () => {
    const inputField = page.getByPlaceholder('Etsi nimellä tai hakusanalla, esim toiminta-avustus')
    await inputField.clear();

    await page.getByRole('button', {name: 'Etsi'}).click();

    const searchResultLinks = page.locator(".application_search--link")
    const firstLink = searchResultLinks.first()
    await firstLink.click()

    const pageTitle = page.url()
    expect(pageTitle).toContain("tietoa-avustuksista")
  });

  test.afterAll(async () => {
    await page.close();
  });
})

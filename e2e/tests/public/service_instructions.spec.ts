import {Page, expect, test} from '@playwright/test';


test.describe("Service instructions", () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
  });

  test.beforeEach(async () => {
    await page.goto('/fi/ohjeita-hakijalle/palvelun-kayttoohjeet');
  });

  test('page title', async () => {
    const pageTitle = await page.title();
    expect(pageTitle).toContain("Palvelun käyttöohjeet");
  });

  test('heading', async () => {
    await expect(await page.getByRole('heading', {name: /Palvelun käyttöohjeet/})).toBeVisible();
  });

  test('buttons', async () => {
    await expect(await page.getByRole('button', {name: 'Kirjautuminen ja tunnistautuminen', exact: true})).toBeVisible();
    await expect(await page.getByRole('button', {name: 'Omat tiedot', exact: true})).toBeVisible();
    await expect(await page.getByRole('button', {name: 'Hakemus ja liitteet', exact: true})).toBeVisible();
    await expect(await page.getByRole('button', {name: 'Oma asiointi ja viestitoiminnallisuus', exact: true})).toBeVisible();
  });

  test.afterAll(async () => {
    await page.close();
  });
})

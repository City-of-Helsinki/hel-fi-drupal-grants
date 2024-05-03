import {Page, expect, test} from '@playwright/test';


test.describe("Frontpage", () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
  });

  test.beforeEach(async () => {
    await page.goto('/fi/avustukset');
  })

  test('title', async () => {
    await expect(await page.title()).toContain("Avustukset | Helsingin kaupunki");
  });

  test('hero', async () => {
    await expect(await page.locator('.hero').getByRole('heading', {name: 'Avustukset'})).toBeVisible()
  });

  test('Tällä Sivuilla section', async () => {
    await expect(await page.locator(".component--list-of-links").getByRole('link', {name: 'Tietoa avustuksista'})).toBeVisible();
    await expect(await page.locator(".component--list-of-links").getByRole('link', {name: 'Etsi avustusta'})).toBeVisible();
    await expect(await page.locator(".component--list-of-links").getByRole('link', {name: 'Ohjeita hakijalle'})).toBeVisible();
  });

  test('banner', async () => {
    await expect(await page.getByRole('heading', {name: 'Pääset täyttämään hakemusta kirjautumalla omaan asiointiin ja luomalla hakijaprofiilin'})).toBeVisible();
    await expect(await page.getByRole('link', {name: 'Kirjaudu sisään'})).toBeVisible();
  });

  test('help section', async () => {
    await expect(await page.locator(".liftup-with-image").getByRole('heading', {name: 'Tarvitsetko apua hakemuksen tekemiseen?'})).toBeVisible();
    await expect(await page.locator(".liftup-with-image").getByRole('link', {name: 'Ohjeita hakijalle'})).toBeVisible()
    await expect(await page.locator(".liftup-with-image").getByRole('link', {name: 'Tietoa avustuksista'})).toBeVisible()
  });

  test('login button', async () => {
    await expect(await page.locator('.profile__login-link')).toHaveText('Kirjaudu');
  });

  test('news section', async () => {
    await expect(await page.getByRole('heading', {name: 'Avustusten uutisia'})).toBeVisible();
    await expect(await page.getByRole('link', {name: 'Katso kaikki uutiset'})).toBeVisible();
  })

  test('Sinua Voisi Kiinnostaa section', async () => {
    await expect(await page.getByRole('heading', {name: 'Sinua voisi kiinnostaa'})).toBeVisible()
    await expect(await page.getByRole('link', {name: 'Yleiset avustusehdot'})).toBeVisible()
    await expect(await page.getByRole('link', {name: 'Tilavaraukset'})).toBeVisible()
    await expect(await page.getByRole('link', {name: 'Päätökset-palvelu'})).toBeVisible()
  });

  test.afterAll(async () => {
    await page.close();
  });
})

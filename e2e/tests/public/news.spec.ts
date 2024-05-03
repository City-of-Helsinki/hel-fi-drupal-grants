import {Page, expect, test} from '@playwright/test';

/*
test.describe("News page", () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
  });

  test.beforeEach(async () => {
    await page.goto('/fi/uutiset');
  });

  test('title', async () => {
    const pageTitle = await page.title()
    expect(pageTitle).toContain('Ajankohtaista avustuksista')
  });

  test('header', async () => {
    await expect(page.getByRole('heading', {name: 'Pääuutiset'})).toBeVisible()
  });

  test('contains atleast one news article', async () => {
    const articleCount = await page.locator('#block-views-block-frontpage-news-main-news').getByRole('listitem').count();
    expect(articleCount).toBeGreaterThan(0)
  });

  test('news article can be opened', async () => {
    const firstLink = page.locator('#block-views-block-frontpage-news-main-news').getByRole('listitem').first()
    await firstLink.click()
    await expect(page.locator(".components--news")).toBeVisible()
  })

  test.afterAll(async () => {
    await page.close();
  });
});
*/

import {Page, expect, test} from '@playwright/test';

test.describe('Navigation features', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage();
    await page.goto('/fi/avustukset');
  });

  test.beforeEach(async () => {

  });

  test.afterAll(async () => {
    await page.close();
  });

  test('Main navigation: Top-level items', async () => {
    const topLevelItems = await page.locator('ul.menu--level-0 > li.menu__item');
    const topLevelItemsCount = await topLevelItems.count();
    await expect(topLevelItems.first()).toBeVisible();
    await expect(topLevelItemsCount).toBeGreaterThanOrEqual(3);
  });

  test('Main navigation: Sub-level items', async () => {
    const toggleButton = await page.locator('li.menu__item--children .menu__toggle-button');
    await toggleButton.first().click();
    const subItems = await page.locator('ul.menu--level-1:visible > li');
    await expect(subItems.first()).toBeVisible();
    await expect(toggleButton.first()).toHaveAttribute('aria-expanded', 'true');
  });

  test('Top-level footer: Navigation items', async () => {
    const footerLinks = await page.locator('.footer-top li.menu__item');
    const footerLinksCount = await footerLinks.count();
    await expect(footerLinksCount).toBeGreaterThanOrEqual(5);
  });

  test('Bottom-level footer: Navigation items', async () => {
    await expect(page.getByRole('link', {name: 'Saavutettavuusseloste'})).toBeVisible();
    await expect(page.getByRole('link', {name: 'Tietopyynnöt'})).toBeVisible();
    await expect(page.getByRole('link', {name: 'Tietoa hel.fistä'})).toBeVisible();
  });

});

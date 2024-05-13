import {Page, expect, test} from '@playwright/test';
import {logger} from "../../utils/logger";

test.describe('Navigation features', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage();
    await page.goto('/fi/avustukset');
  });

  test.afterAll(async () => {
    await page.close();
  });

  test('Main navigation: Top-level items', async () => {
    logger('Validating main navigation top-level items...');
    const topLevelItems = await page.locator('ul.menu--level-0 > li.menu__item');
    const topLevelItemsCount = await topLevelItems.count();
    await expect(topLevelItems.first()).toBeVisible();
    await expect(topLevelItemsCount).toBeGreaterThanOrEqual(3);
    logger('Main navigation top-level items validated.');
  });

  test('Main navigation: Sub-level toggling functionality', async () => {
    logger('Validating main navigation sub-level toggling...');
    const toggleButton = await page.locator('li.menu__item--children .menu__toggle-button');
    await toggleButton.first().click();
    const subItems = await page.locator('ul.menu--level-1:visible > li');
    await expect(subItems.first()).toBeVisible();
    await expect(toggleButton.first()).toHaveAttribute('aria-expanded', 'true');
    logger('Main navigation sub-level toggling validated.');
  });

  test('Top-level footer: Navigation items', async () => {
    logger('Validating top-level footer navigation items...');
    const footerLinks = await page.locator('.footer-top li.menu__item');
    const footerLinksCount = await footerLinks.count();
    await expect(footerLinksCount).toBeGreaterThanOrEqual(5);
    logger('Top-level footer navigation items validated.');
  });

  test('Bottom-level footer: Navigation items', async () => {
    logger('Validating bottom-level footer navigation items...');
    await expect(page.getByRole('link', {name: 'Saavutettavuusseloste'})).toBeVisible();
    await expect(page.getByRole('link', {name: 'Tietopyynnöt'})).toBeVisible();
    await expect(page.getByRole('link', {name: 'Tietoa hel.fistä'})).toBeVisible();
    logger('Bottom-level footer navigation items validated.');
  });

});

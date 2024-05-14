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

  test('Main navigation: Sub-level toggling functionality', async () => {
    logger('Validating main navigation sub-level toggling...');
    const toggleButton = await page.locator('li.menu__item--children .menu__toggle-button');
    await toggleButton.first().click();
    const subItems = await page.locator('ul.menu--level-1:visible > li');
    await expect(subItems.first()).toBeVisible();
    await expect(toggleButton.first()).toHaveAttribute('aria-expanded', 'true');
    logger('Main navigation sub-level toggling validated.');
  });

});

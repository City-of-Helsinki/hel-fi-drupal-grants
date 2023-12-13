import {test as setup, expect} from '@playwright/test';

setup('Maintenance mode should be off', async ({page}) => {
  console.log('Check maintenance mode')
  await page.goto('/');
  await expect(page.locator(".maintenance-page")).toBeHidden();
});

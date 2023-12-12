import { test as setup, expect } from '@playwright/test';

setup('Maintenance mode should be off', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator(".maintenance-page")).toBeHidden();
});
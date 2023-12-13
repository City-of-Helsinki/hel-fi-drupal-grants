import { expect, test as setup } from '@playwright/test';
import { removeGrantProfiles } from '../../utils/removeDocumentsFromAtv';
import { TEST_USER_UUID } from '../../utils/test_data';


setup('Check for maintenance mode', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator(".maintenance-page")).toBeHidden();
});

setup('Remove existing grant profiles', async () => {
    await removeGrantProfiles(TEST_USER_UUID); 
});

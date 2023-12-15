import { expect, test as setup } from '@playwright/test';
import { deleteGrantProfiles } from '../../utils/deleteGrantProfilesFromATV';
import { TEST_USER_UUID } from '../../utils/test_data';

setup('Check for maintenance mode', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('.maintenance-page')).toBeHidden();
});

setup('Delete test user grant profiles', async () => {
  await deleteGrantProfiles(TEST_USER_UUID);
});

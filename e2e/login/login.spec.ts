import { test } from '@playwright/test';
import { TEST_SSN } from '../utils/test_data';
import { login } from '../utils/helpers';

test('can login through Tunnistamo', async ({ page }) => {
 await login(page)
});

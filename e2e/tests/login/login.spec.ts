import { test } from '@playwright/test';
import { login } from '../../utils/helpers';

test('can login through Tunnistamo', async ({ page }) => {
    await login(page)
});

import { test as setup } from '@playwright/test';
import { existsSync, readFileSync } from 'fs';
import { AUTH_FILE_PATH, loginAndSaveStorageState } from '../utils/helpers';


setup.setTimeout(60000)

setup('authenticate', async ({ page }) => {
    const authFileExists = existsSync(AUTH_FILE_PATH);

    if (!authFileExists) {
        await loginAndSaveStorageState(page);
        return;
    }

    const storageState = JSON.parse(readFileSync(AUTH_FILE_PATH, 'utf8'));
    const sessionCookie = storageState.cookies.find(c => c.name.startsWith('SSESS'));
    const sessionCookieIsValid = Boolean(sessionCookie && sessionCookie.expires > Math.floor(Date.now() / 1000));

    if (!sessionCookieIsValid) {
        await loginAndSaveStorageState(page);
    }
});

import {test as setup} from '@playwright/test';
import {existsSync, readFileSync} from 'fs';
import {AUTH_FILE_PATH, loginAndSaveStorageState} from '../utils/helpers';


setup.setTimeout(60000)

setup('authenticate', async ({page}) => {
  console.log('Authenticate...')
  const authFileExists = existsSync(AUTH_FILE_PATH);

  if (!authFileExists) {
    console.log('No session data saved, go login');
    await loginAndSaveStorageState(page);
    return;
  }

  const storageState = JSON.parse(readFileSync(AUTH_FILE_PATH, 'utf8'));
  const sessionCookie = storageState.cookies.find((c: {
    name: string;
  }) => c.name.startsWith('SSESS'));
  const sessionCookieIsValid = Boolean(sessionCookie && sessionCookie.expires > Math.floor(Date.now() / 1000));

  if (!sessionCookieIsValid) {
    console.log('Session cookie invalid, do login');
    await loginAndSaveStorageState(page);
  }
});

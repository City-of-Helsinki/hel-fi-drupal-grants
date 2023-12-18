import {Page, test as setup} from '@playwright/test';
import {existsSync, readFileSync} from 'fs';
import {AUTH_FILE_PATH, loginAndSaveStorageState} from '../utils/helpers';

/**
 * Checks the existence and validity of stored session cookie.
 *
 *
 * @param page
 */
const checkLoginStateAndLogin = async (page: Page) => {
  console.log('Authenticate...')
  const authFileExists = existsSync(AUTH_FILE_PATH);

  // If no auth file saved, login and sate state.
  if (!authFileExists) {
    console.log('No session data saved, go login');
    await loginAndSaveStorageState(page);
    return;
  }

  // Try to read storage from auth file
  const storageState = JSON.parse(readFileSync(AUTH_FILE_PATH, 'utf8'));
  const sessionCookie = storageState.cookies.find((c: {
    name: string;
  }) => c.name.startsWith('SSESS'));

  // If session cookie exists, add it to context.
  if (sessionCookie) {
    console.log('Cookie found, add to context');
    await page.context().addCookies([sessionCookie]);
  } else {
    console.log('Session cookie not found, do login');
    await loginAndSaveStorageState(page);
    return;
  }

  // Check session by visiting user page
  console.log('Visit user page to check session validity');
  await page.goto('/fi/user');
  const actualUrl = page.url();

  // If user is redirected to either of following urls
  if (actualUrl.includes('/asiointirooli-valtuutus') || actualUrl.includes('/oma-asiointi/hakuprofiili')) {
    // We know we have valid session, no need to anything else.
    console.log('User session valid!', actualUrl);
  } else {
    // If we get any other page, probably login page, do login.
    console.log('Session cookie invalid, do login');
    await loginAndSaveStorageState(page);
  }

}



export {
  checkLoginStateAndLogin
}

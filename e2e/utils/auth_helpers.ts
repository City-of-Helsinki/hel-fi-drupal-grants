import {Page} from '@playwright/test';
import {logger} from "./logger";
import {existsSync, readFileSync} from 'fs';

type Role = "REGISTERED_COMMUNITY" | "UNREGISTERED_COMMUNITY" | "PRIVATE_PERSON";
type Mode = "new" | "existing";

const AUTH_FILE_PATH = '.auth/user.json';

/**
 * Select selectRole function.
 *
 * @param page
 * @param role
 * @param mode
 */
const selectRole = async (page: Page, role: Role, mode: Mode = 'existing') => {
  await checkLoginStateAndLogin(page);
  await page.goto("/fi/asiointirooli-valtuutus");

  const roleIsLoggedIn = await page.locator(`body.grants-role-${role.toLowerCase()}`).count() > 0;
  if (roleIsLoggedIn) {
    logger(`${role}, mandate exists`);
    return;
  }

  logger(`Get mandate for ${role}`);
  switch (role) {
    case "REGISTERED_COMMUNITY":
      await selectRegisteredCommunityRole(page);
      break;
    case "UNREGISTERED_COMMUNITY":
      await selectUnregisteredCommunityRole(page, mode);
      break;
    case "PRIVATE_PERSON":
      await selectPrivatePersonRole(page);
      break;
  }
}

/**
 * The selectRegisteredCommunityRole function.
 *
 * @param page
 */
const selectRegisteredCommunityRole = async (page: Page) => {
  await page.locator('[name="registered_community"]').click();
  await page.locator('input[type="radio"]').first().check({ force: true });
  await page.locator('[data-test="perform-confirm"]').click();
}

/**
 * The selectUnregisteredCommunityRole function.
 *
 * @param page
 * @param mode
 */
const selectUnregisteredCommunityRole = async (page: Page, mode: Mode) => {
  if (mode === 'new') {
    await page.locator('#edit-unregistered-community-selection').selectOption('new');
    await page.locator('#edit-submit--2').click();
  }
  if (mode === 'existing') {
    await page.locator('#edit-unregistered-community-selection').selectOption({ index: 2 });
  }
  await page.locator('[name="unregistered_community"]').click();
}

/**
 * The selectPrivatePersonRole function.
 *
 * @param page
 */
const selectPrivatePersonRole = async (page: Page) => {
  await page.locator('[name="private_person"]').click();
}

const login = async (page: Page, SSN?: string) => {

  logger('LOGIN');

  await page.goto('/fi/user/login');
  await page.locator("#edit-openid-connect-client-tunnistamo-login").click();
  await page.locator("#fakevetuma2").click()
  await page.locator("#hetu_input").fill(SSN ?? process.env.TEST_USER_SSN ?? '');
  await page.locator('.box').click()
  await page.locator('#tunnistaudu').click();
  await page.locator('#continue-button').click();
  await page.waitForSelector('text="Helsingin kaupunki"');
}


const loginAndSaveStorageState = async (page: Page) => {
  await login(page);
  await page.context().storageState({path: AUTH_FILE_PATH});
}

/**
 * Checks the existence and validity of stored session cookie.
 *
 *
 * @param page
 */
const checkLoginStateAndLogin = async (page: Page) => {
  logger('Authenticate...')
  const authFileExists = existsSync(AUTH_FILE_PATH);

  // If no auth file saved, login and sate state.
  if (!authFileExists) {
    logger('No session data saved, go login');
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
    logger('Cookie found, add to context');
    await page.context().addCookies([sessionCookie]);
  } else {
    logger('Session cookie not found, do login');
    await loginAndSaveStorageState(page);
    return;
  }

  // Check session by visiting user page
  logger('Visit user page to check session validity');
  await page.goto('/fi/user');
  const actualUrl = page.url();

  // If user is redirected to either of following urls
  if (actualUrl.includes('/asiointirooli-valtuutus') || actualUrl.includes('/oma-asiointi/hakuprofiili')) {
    // We know we have valid session, no need to anything else.
    logger('User session valid!');
  } else {
    // If we get any other page, probably login page, do login.
    logger('Session cookie invalid, do login');
    await loginAndSaveStorageState(page);
  }

}


export {
  selectRole,
}

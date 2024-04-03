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
  }
  if (mode === 'existing') {
    await page.locator('#edit-unregistered-community-selection').selectOption({index: 2});
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

/**
 * The login function.
 *
 * @param page
 * @param SSN
 */
const login = async (page: Page, SSN?: string) => {
  logger('Logging in...');
  await page.goto('/fi/user/login');
  await page.locator("#edit-openid-connect-client-tunnistamo-login").click();
  await page.locator("#fakevetuma2").click()
  await page.locator("#hetu_input").fill(SSN ?? process.env.TEST_USER_SSN ?? '');
  await page.locator('.box').click()
  await page.locator('#tunnistaudu').click();
  await page.locator('#continue-button').click();
  await page.waitForSelector('text="Helsingin kaupunki"');
}

/**
 * The loginAndSaveStorageState function.
 *
 * @param page
 */
const loginAndSaveStorageState = async (page: Page) => {
  await login(page);
  await page.context().storageState({path: AUTH_FILE_PATH});
}

/**
 * The authFileExists function.
 */
const authFileExists = (): boolean => {
  logger('Locating auth file...')
  const authFileExists = existsSync(AUTH_FILE_PATH);

  if (!authFileExists) {
    logger('Auth file does not exists.');
    return false;
  }

  logger('Auth file exists.');
  return true;
}

/**
 * The getSessionCookie function.
 */
const getSessionCookie = (): boolean | any => {
  logger('Getting session cookie...')
  const storageState = JSON.parse(readFileSync(AUTH_FILE_PATH, 'utf8'));
  const sessionCookie = storageState.cookies.find((c: { name: string; }) => c.name.startsWith('SSESS'));

  if (!sessionCookie) {
    logger('Session cookie not found.');
    return false;
  }

  logger('Session cookie found.');
  return sessionCookie;
}

/**
 * The sessionIsValid function.
 *
 * @param page
 */
const sessionIsValid = async (page: Page): Promise<boolean> => {
  logger('Visit user page to check session validity...');
  await page.goto('/fi/user');
  const actualUrl = page.url();

  if (!actualUrl.includes('/asiointirooli-valtuutus') &&
      !actualUrl.includes('/oma-asiointi/hakuprofiili')) {
    logger('Session is not valid.');
    return false;
  }

  logger('Session is valid.');
  return true;
}

/**
 * The checkLoginStateAndLogin function.
 *
 * @param page
 */
const checkLoginStateAndLogin = async (page: Page) => {
  logger('Authenticate...')

  // If no auth file exists, login and save state.
  if (!authFileExists()) {
    await loginAndSaveStorageState(page);
    return;
  }

  // If no session cookie exists, login and save state.
  const sessionCookie = getSessionCookie();
  if (!sessionCookie) {
    await loginAndSaveStorageState(page);
    return;
  }

  // Add the found cookie to page context.
  logger('Adding session cookie to context.');
  await page.context().addCookies([sessionCookie]);

  // Make sure the session is valid.
  const hasValidSession = await sessionIsValid(page);
  if (!hasValidSession) {
    await loginAndSaveStorageState(page);
    return;
  }
}

export {
  selectRole,
  checkLoginStateAndLogin
}

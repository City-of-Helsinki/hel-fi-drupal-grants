import {Page} from '@playwright/test';
import {logger} from "./logger";
import {existsSync, readFileSync} from 'fs';
import {acceptCookies, logCurrentUrl} from "./helpers";
import {chmodSync} from "node:fs";

type Role = "REGISTERED_COMMUNITY" | "UNREGISTERED_COMMUNITY" | "PRIVATE_PERSON";
type Mode = "new" | "existing";
const AUTH_FILE_PATH = '.auth/user.json';

/**
 * Select selectRole function.
 *
 * This function logs in a user by calling checkLoginStateAndLogin().
 * After this, the passed in roll is selected by visiting "/asiointirooli-valtuutus".
 * Role selection is skipped if the role is already selected (class found in body).
 *
 * @param page
 *   Playwright page object.
 * @param role
 *   A string indicating which role needs to be selected.
 * @param mode
 *   A string indicating if we're creating a new unregistered community
 *   or selecting an existing one.
 */
const selectRole = async (page: Page, role: Role, mode: Mode = 'existing') => {
  // Before selecting the role, make an attempt to accept the cookies.
  logger(`Make an attempt to accept the cookies.`);
  await page.goto("/fi");
  await acceptCookies(page);

  // Check login state and login.
  await checkLoginStateAndLogin(page);
  await page.goto("/fi/asiointirooli-valtuutus");
  await logCurrentUrl(page);

  const roleIsLoggedIn = await page.locator("body")
    .evaluate((el, role) => el.classList.contains(`grants-role-${role.toLowerCase()}`), role);

  if (roleIsLoggedIn) {
    logger(`${role}, mandate exists.`);
    return;
  }

  logger(`Get mandate for ${role}...`);
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
 * This function selects the unregistered community role.
 *
 * @param page
 *   Playwright page object.
 */
const selectRegisteredCommunityRole = async (page: Page) => {
  await page.locator('[name="registered_community"]').click();
  await page.locator('input[type="radio"]').first().check({ force: true });
  await page.locator('[data-test="perform-confirm"]').click();
  logger('Selected registered community role.');
}

/**
 * The selectUnregisteredCommunityRole function.
 *
 * This function selects the unregistered community role.
 *
 * @param mode
 *   A string indicating if we're creating a new unregistered community
 *   or selecting an existing one.
 * @param page
 *   Playwright page object.
 */
const selectUnregisteredCommunityRole = async (page: Page, mode: Mode) => {
  if (mode === 'new') {
    await page.locator('#edit-unregistered-community-selection').selectOption('new');
  }
  if (mode === 'existing') {
    await page.locator('#edit-unregistered-community-selection').selectOption({index: 2});
  }
  await page.locator('[name="unregistered_community"]').click();
  logger('Selected unregistered community role.');
}

/**
 * The selectPrivatePersonRole function.
 *
 * This function selects the private person role.
 *
 * @param page
 *   Playwright page object.
 */
const selectPrivatePersonRole = async (page: Page) => {
  await page.locator('[name="private_person"]').click();
  logger('Selected private person role.');
}

/**
 * The login function.
 *
 * This function logs in a user with the provided SSN.
 *
 * @param page
 *   Playwright page object.
 * @param SSN
 *   A users SSN (social security number).
 */
const login = async (page: Page, SSN?: string) => {
  logger('Logging in...');
  await page.goto('/fi/user/login');
  await logCurrentUrl(page);
  await page.locator("#edit-openid-connect-client-tunnistamo-login").click();
  await page.locator("#fakevetuma2").click()
  await page.locator("#hetu_input").fill(SSN ?? process.env.TEST_USER_SSN ?? '');
  await page.locator('.box').click()
  await page.locator('#tunnistaudu').click();
  await page.locator('#continue-button').click();
  await page.waitForSelector('text="Helsingin kaupunki"');
  logger('User logged in.')
}

/**
 * The loginAndSaveStorageState function.
 *
 * This function calls login() and stores the session
 * data in the auth file. This data can be used to
 * validate a session by extracting a session cookie.
 *
 * @param page
 *   Playwright page object.
 */
const loginAndSaveStorageState = async (page: Page) => {
  logger('Logging in and creating auth file...');
  await login(page);
  logger('Creating auth file...');
  await page.context().storageState({path: AUTH_FILE_PATH});
  logger('Auth file created.')
  // Change file permissions to allow deletion
  chmodSync(AUTH_FILE_PATH, 0o666);
  logger('Auth file permissions updated.');
}

/**
 * The authFileExists function.
 *
 * This function attempts to locate an auth file.
 *
 * @return bool
 *   False if the auth file does not exist, true otherwise.
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
 *
 * This function attempts to locate a session cookie
 * inside the auth file. If the cookie is found, it is
 * returned.
 *
 * @return bool|any
 *   False if a session cookie is not found, or the session cookie.
 */
const getSessionCookie = (): boolean | any => {
  logger('Getting session cookie...');
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
 * This function makes sure a session is valid
 * by visiting "/fi/user" and making sure that the user
 * is redirected to either:
 *
 * A) /asiointirooli-valtuutus
 * B) /oma-asiointi/hakuprofiili
 *
 * @param page
 *   Playwright page object.
 *
 * @return boolean
 *   A boolean indicating if the session is valid or not.
 */
const sessionIsValid = async (page: Page): Promise<boolean> => {
  logger('Visit user page to check session validity...');
  await page.goto('/fi/user');
  await logCurrentUrl(page);
  const actualUrl = page.url();

  if (
    !actualUrl.includes('/asiointirooli-valtuutus') &&
    !actualUrl.includes('/oma-asiointi/hakuprofiili')
  ) {
    logger('Session is not valid.');
    return false;
  }

  logger('Session is valid.');
  return true;
}

/**
 * The checkLoginStateAndLogin function.
 *
 * This function performs the authentication flow by:
 * 1. Making sure we have an auth file.
 * 2. Making sure the auth file contains a valid session cookie.
 * 3. Making sure the session cookie is valid.
 *
 * If any of the steps fail, the user is logged in again,
 * and the state is saved.
 *
 * @param page
 *   Playwright page object.
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

  // If the session isn't valid, login and save state.
  const hasValidSession = await sessionIsValid(page);
  if (!hasValidSession) {
    await loginAndSaveStorageState(page);
    return;
  }
}

export {
  Role,
  selectRole,
  checkLoginStateAndLogin
}

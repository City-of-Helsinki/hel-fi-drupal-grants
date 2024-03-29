import {Page, expect} from '@playwright/test';
import {logger} from "./logger";
import {existsSync, readFileSync} from 'fs';

type Role = "REGISTERED_COMMUNITY" | "UNREGISTERED_COMMUNITY" | "PRIVATE_PERSON"

const AUTH_FILE_PATH = '.auth/user.json';

/**
 * Select role for user session to apply grants with.
 *
 * @param page
 * @param role
 * @param mode
 */
const selectRole = async (page: Page, role: Role, mode: string = 'existing') => {

  await checkLoginStateAndLogin(page);

  await page.goto("/fi/asiointirooli-valtuutus");

  const loggedInAsRegisteredCommunity = await page.locator("body")
    .evaluate(el => el.classList.contains("grants-role-registered_community"));

  const loggedAsPrivatePerson = await page.locator("body")
    .evaluate(el => el.classList.contains("grants-role-private_person"));

  const loggedInAsUnregisteredCommunity = await page.locator("body")
    .evaluate(el => el.classList.contains("grants-role-unregistered_community"));

  if (role === 'REGISTERED_COMMUNITY' && !loggedInAsRegisteredCommunity) {
    logger('Get mandate for REGISTERED_COMMUNITY')
    await selectRegisteredCommunityRole(page);
  } else if(role === 'REGISTERED_COMMUNITY') {
    logger('REGISTERED_COMMUNITY, mandate exists');

  }

  if (role === 'UNREGISTERED_COMMUNITY' && !loggedInAsUnregisteredCommunity) {
    logger('Get mandate for UNREGISTERED_COMMUNITY')
    if (mode === 'existing') {
      await selectUnregisteredCommunityRole(page);
    } else if (mode === 'new') {
      await page.goto('/fi/asiointirooli-valtuutus');
      await page.locator('#edit-unregistered-community-selection').selectOption('new');
      await page.locator('#edit-submit--2').click();
      // await page.getByRole('button', {name: 'Lisää uusi Rekisteröitymätön yhteisö tai ryhmä'}).click();
    }
  } else if(role === 'UNREGISTERED_COMMUNITY') {
    logger('UNREGISTERED_COMMUNITY, mandate exists');
  }


  if (role === 'PRIVATE_PERSON' && !loggedAsPrivatePerson) {
    logger('Get mandate for PRIVATE_PERSON');
    await selectPrivatePersonRole(page);
  } else if(role === 'PRIVATE_PERSON') {
    logger('PRIVATE_PERSON, mandate exists');
  }

}

const selectRegisteredCommunityRole = async (page: Page) => {
  const registeredCommunityButton = page.locator('[name="registered_community"]')
  await expect(registeredCommunityButton).toBeVisible()
  await registeredCommunityButton.click()
  const firstCompanyRow = page.locator('input[type="radio"]').first()
  await firstCompanyRow.check({force: true})
  await page.locator('[data-test="perform-confirm"]').click()
}

const selectUnregisteredCommunityRole = async (page: Page) => {
  await page.locator('#edit-unregistered-community-selection').selectOption({index: 2});
  await page.locator('[name="unregistered_community"]').click()
}

const selectPrivatePersonRole = async (page: Page) => {
  await page.locator('[name="private_person"]').click()
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
  checkLoginStateAndLogin,
  login,
  loginAndSaveStorageState,
  selectRole,
  selectUnregisteredCommunityRole,
  selectRegisteredCommunityRole,
  selectPrivatePersonRole,
  AUTH_FILE_PATH
}

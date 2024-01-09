import { Page, test } from '@playwright/test';
import { AUTH_FILE_PATH } from '../../utils/constants';
import { PrivatePersonProfilePage } from './pom/privateProfile';
import { UnregisteredCommunityProfilePage } from './pom/unregisteredProfile';
import { RegisteredCommunityProfilePage } from './pom/registeredProfile';
import { selectRole } from '../../utils/role';

test.describe('Profiles', () => {
  let page: Page;

  test.beforeAll('Log in', async ({ browser }) => {
    page = await browser.newPage();
  });

  test('Private person', async () => {
    await selectRole(page, 'PRIVATE_PERSON');
    const profilePage = new PrivatePersonProfilePage(page);
    await profilePage.goToEditPage();
    await profilePage.setupProfile();
    await profilePage.bankAccountIsRequired();
    await profilePage.checkOmaAsiointiPage();
  });

  test('Unregistered community', async () => {
    const profilePage = new UnregisteredCommunityProfilePage(page);
    await profilePage.createCommunity();
    await profilePage.checkHeadings();
    await profilePage.checkRequirementForOfficial();
    await profilePage.bankAccountIsRequired();
  });

  test('Registered community', async () => {
    await selectRole(page, 'REGISTERED_COMMUNITY');
    const profilePage = new RegisteredCommunityProfilePage(page);
    await profilePage.setupProfile();
    await profilePage.bankAccountIsRequired();
  });

  test.afterAll(async () => await page.context().storageState({ path: AUTH_FILE_PATH }));
});

import { Page, test } from '@playwright/test';
import { selectRole } from '../../utils/role';
import { UnregisteredCommunityProfilePage } from './pom/unregisteredProfile';

test.describe('Hakuprofiili Unregistered Community', () => {
  let page: Page;
  let profilePage: UnregisteredCommunityProfilePage;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    profilePage = new UnregisteredCommunityProfilePage(page);
    await selectRole(page, 'UNREGISTERED_COMMUNITY');
  });

  test.beforeEach(async () => await profilePage.goto());
  test.afterAll(async () => await page.close());

  test('Headings', async () => await profilePage.checkHeadings());

  test('Contact information can be updated', async () => await profilePage.updateInfo());

  test('An official is required', async () => await profilePage.checkRequirementForOfficial());

  test('Bank account is required', async () => await profilePage.bankAccountIsRequired());
});

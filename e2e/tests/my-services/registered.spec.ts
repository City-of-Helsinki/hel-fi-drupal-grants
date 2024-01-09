import { Page, test } from '@playwright/test';
import { selectRole } from '../../utils/role';
import { RegisteredCommunityProfilePage } from './pom/registeredProfile';

test.describe('Hakuprofiili Registered Community', () => {
  let page: Page;
  let profilePage: RegisteredCommunityProfilePage;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    profilePage = new RegisteredCommunityProfilePage(page);
    await selectRole(page, 'REGISTERED_COMMUNITY');
  });

  test.beforeEach(async () => await profilePage.goto());
  test.afterAll(async () => await page.close());

  test('Contact information can be updated', async () => await profilePage.updateInfo());

  test('Bank account requirement', async () => await profilePage.bankAccountIsRequired());
});

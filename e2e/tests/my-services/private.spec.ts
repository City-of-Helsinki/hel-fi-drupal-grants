import { Page, test } from '@playwright/test';
import { selectRole } from '../../utils/role';
import { PrivatePersonProfilePage } from './pom/privateProfile';

test.describe('Hakuprofiili Private person', () => {
  let page: Page;
  let profilePage: PrivatePersonProfilePage;

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    profilePage = new PrivatePersonProfilePage(page);
    await selectRole(page, 'PRIVATE_PERSON');
  });

  test.beforeEach(async () => await profilePage.goto());
  test.afterAll(async () => await page.close());

  test('Contact information can be updated', async () => await profilePage.updateInfo());

  test('Bank account requirement', async () => await profilePage.bankAccountIsRequired());
});

import {faker} from '@faker-js/faker';
import {Locator, Page, expect, test} from '@playwright/test';
import {
  selectRole,
  setupUnregisteredCommunity,
  slowLocator
} from '../../utils/helpers';
import {checkContactInfoPrivatePerson} from '../../utils/profile_helpers';
import {
  fillForm,
  FormData
} from '../../utils/form_helpers'

import {
  profileData,
  applicationData
} from '../../utils/test_data'

test.describe('Private Person', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    page.locator = slowLocator(page, 500);
    await selectRole(page, 'PRIVATE_PERSON');
  });

  test('Test oma asiointi', async () => {
    console.log('Oma asiointi')
    await page.goto("/fi/oma-asiointi");
  });

  test('Test oma hakuprofiili', async () => {
    console.log('Hakuprofiili');
    await page.goto("/fi/oma-asiointi/hakuprofiili");
    await checkContactInfoPrivatePerson(page);
  });

  test('Test edit hakuprofiili', async () => {
    console.log('Hakuprofiili edit');
    await page.goto("/fi/oma-asiointi/hakuprofiili/muokkaa");

    await fillForm(page, profileData.success);
  });


})

import {Locator, Page, expect, test} from '@playwright/test';
import {
  acceptCookies,
  selectRole,
  setupUnregisteredCommunity,
  slowLocator
} from '../../utils/helpers';
import {checkContactInfoPrivatePerson} from '../../utils/profile_helpers';
import {
  fillForm,
} from '../../utils/form_helpers'

import {
  profileDataUnregisteredCommunity as profileData,
  applicationData
} from '../../utils/data/test_data'


test.describe('Grants Profile - Unregistered Community', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    page.locator = slowLocator(page, 500);

    await selectRole(page, 'UNREGISTERED_COMMUNITY', 'new');
  });

  test('Test edit hakuprofiili', async () => {
    console.log('Hakuprofiili edit');
    await page.goto("/fi/oma-asiointi/hakuprofiili/muokkaa");

    // await acceptCookies(page);

    // @ts-ignore
    await fillForm(page, profileData.success, 'grants-profile-unregistered-community');
  });

  // test('Test oma hakuprofiili', async () => {
  //   console.log('Hakuprofiili');
  //   await page.goto("/fi/oma-asiointi/hakuprofiili");
  //
  //   // @ts-ignore
  //   // await checkContactInfoPrivatePerson(page, profileData.success);
  //
  //   // joko tässä tai sit tossa ylläolevassa funkkarissa vois tarkistaa myös,
  //   // että kaikki tallennetut kentät löytyy myös profiilista.
  //
  // });

  // test('Test oma asiointi', async () => {
  //   console.log('Oma asiointi')
  //   await page.goto("/fi/oma-asiointi");
  // });


})

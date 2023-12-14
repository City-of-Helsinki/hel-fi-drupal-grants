import {faker} from '@faker-js/faker';
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
  profileDataPrivatePerson,
  applicationData,
  FormData
} from '../../utils/data/test_data'
import PlaywrightConfig from "../../playwright.config";

test.describe('Grants Profile - Private Person', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    // page.locator = slowLocator(page, 500);

    await selectRole(page, 'PRIVATE_PERSON');
  });

  test('Test oma asiointi', async () => {
    console.log('Oma asiointi')
    await page.goto("/fi/oma-asiointi");
  });

  const testDataArray: [string, FormData][] = Object.entries(profileDataPrivatePerson);
  for (const [key, obj] of testDataArray) {
    test(`Test GrantsProfile form (${obj.title})`, async () => {
      await fillForm(page, obj, obj.formPath, obj.formSelector);
    });
  }

  // test('Test edit hakuprofiili', async () => {
  //   console.log('Hakuprofiili edit');
  //   await page.goto("/fi/oma-asiointi/hakuprofiili/muokkaa");
  //
  //   // await acceptCookies(page);
  //
  //   const testDataArray = Object.entries(profileDataPrivatePerson);
  //
  //
  //
  //   // @ts-ignore
  //   // await fillForm(page, profileDataPrivatePerson.success);
  // });

  test('Test oma hakuprofiili', async () => {
    console.log('Hakuprofiili');
    await page.goto("/fi/oma-asiointi/hakuprofiili");

    // @ts-ignore
    await checkContactInfoPrivatePerson(page, profileDataPrivatePerson.success);

    // joko tässä tai sit tossa ylläolevassa funkkarissa vois tarkistaa myös,
    // että kaikki tallennetut kentät löytyy myös profiilista.

  });


})

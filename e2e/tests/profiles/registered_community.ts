import {Locator, Page, expect, test} from '@playwright/test';
import {
  selectRole,
  slowLocator
} from '../../utils/helpers';

import {
  fillForm,
} from '../../utils/form_helpers'

import {checkContactInfoPrivatePerson} from '../../utils/profile_helpers';

import {
  profileDataRegisteredCommunity as profileData,
  applicationData, FormData, profileDataPrivatePerson
} from '../../utils/data/test_data'

import {TEST_USER_UUID} from '../../utils/data/test_data';
import {
  getAppEnvForATV,
  deleteGrantsProfiles
} from "../../utils/document_helpers";


test.describe('Registered Community - Oma Asiointi', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    page.locator = slowLocator(page, 500);

    await selectRole(page, 'REGISTERED_COMMUNITY');
  });

  // test('Test that oma asiointi page loads', async () => {
  //   await page.goto("/fi/oma-asiointi");
  //   expect(page.url()).toEqual("/fi/oma-asiointi");
  // });
});


test.describe('Registered Community - Grants Profile', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    // page.locator = slowLocator(page, 500);

    await selectRole(page, 'REGISTERED_COMMUNITY');
  });

  test.beforeEach(async () => {
    const deletedDocumentsCount = await deleteGrantsProfiles(TEST_USER_UUID);
    const infoText = `Deleted ${deletedDocumentsCount} grant profiles from ATV)`;
    console.log(infoText);

  })

  // @ts-ignore
  const testDataArray: [string, FormData][] = Object.entries(profileData);
  for (const [key, obj] of testDataArray) {
    test(`Testing...${obj.title}`, async () => {
      await fillForm(page, obj, obj.formPath, obj.formSelector);
      // ehkä tähän väliin pitää laittaa tapa testata tallennuksen onnistumista?
    });
  }


  test('Test Grants profile data', async () => {
    console.log('Hakuprofiili');
    await page.goto("/fi/oma-asiointi/hakuprofiili");

    // @ts-ignore
    // await checkContactInfoPrivatePerson(page, profileDataPrivatePerson.success);

    // joko tässä tai sit tossa ylläolevassa funkkarissa vois tarkistaa myös,
    // että kaikki tallennetut kentät löytyy myös profiilista.

  });


})

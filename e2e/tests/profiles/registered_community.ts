import {Locator, Page, expect, test} from '@playwright/test';
import {
  slowLocator
} from '../../utils/helpers';

import {
  fillProfileForm,
} from '../../utils/form_helpers'

import {checkContactInfoPrivatePerson, runOrSkipTest} from '../../utils/profile_helpers';

import {
  profileDataRegisteredCommunity as profileData,
  applicationData, FormData, profileDataPrivatePerson
} from '../../utils/data/test_data'

import {TEST_USER_UUID} from '../../utils/data/test_data';
import {
  getAppEnvForATV,
  deleteGrantsProfiles
} from "../../utils/document_helpers";

import {selectRole} from "../../utils/auth_helpers";

const profileVariableName = 'profileCreatedPrivate';


test.describe('Registered Community - Oma Asiointi', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    // page.locator = slowLocator(page, 500);

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

// @ts-ignore
  const testDataArray: [string, FormData][] = Object.entries(profileData);
  let successTest: FormData;
  for (const [key, obj] of testDataArray) {

    if (key === 'success') {
      successTest = obj;
    } else {
      runOrSkipTest(`Testing...${obj.title}`, async () => {

        // We must delete here manually profiles, since we don't want to do this always.
        const deletedDocumentsCount = await deleteGrantsProfiles(TEST_USER_UUID);
        const infoText = `Deleted ${deletedDocumentsCount} grant profiles from ATV)`;
        console.log(infoText);

        await fillProfileForm(page, obj, obj.formPath, obj.formSelector);
        // ehkä tähän väliin pitää laittaa tapa testata tallennuksen onnistumista?
      }, profileVariableName, 'registered_community');
    }
  }

  // @ts-ignore
  if (successTest) {
    runOrSkipTest(successTest.title, async () => {

      // We must delete here manually profiles, since we don't want to do this always.
      const deletedDocumentsCount = await deleteGrantsProfiles(TEST_USER_UUID);
      const infoText = `Deleted ${deletedDocumentsCount} grant profiles from ATV)`;
      console.log(infoText, successTest.formSelector);

      await fillProfileForm(page, successTest, successTest.formPath, successTest.formSelector);
    }, profileVariableName, 'registered_community');


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

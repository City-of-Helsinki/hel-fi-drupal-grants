import {Page, test} from '@playwright/test';
import {logger} from "../../utils/logger";
import {
  fillProfileForm,
} from '../../utils/form_helpers'

import {isProfileCreated} from '../../utils/profile_helpers';

import {
  profileDataRegisteredCommunity as profileData,
  FormData
} from '../../utils/data/test_data'

import {
  deleteGrantsProfiles
} from "../../utils/document_helpers";

import {selectRole} from "../../utils/auth_helpers";

const profileVariableName = 'profileCreatedRegistered';
const profileType = 'registered_community';

test.describe('Registered Community - Grants Profile', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    // page.locator = slowLocator(page, 500);

    await selectRole(page, 'REGISTERED_COMMUNITY');
  });

  test.beforeEach(async () => {
    /*
    1. If you want to skip tests during test declaration, you should use test.skip() inside the test.describe() callback.
    2. If you want to skip tests during test execution, you should use test.skip() inside the beforeEach() hook.
    */
    const skip = await isProfileCreated(profileVariableName, profileType);
    test.skip(skip);
  });

  // @ts-ignore
  test('Profile creation', async () => {
    const testDataArray: [string, FormData][] = Object.entries(profileData);
    let successTest: FormData;
    for (const [key, obj] of testDataArray) {

      if (key === 'success') {
        successTest = obj;
      } else {
          // We must delete here manually profiles, since we don't want to do this always.
          const deletedDocumentsCount = await deleteGrantsProfiles(process.env.TEST_USER_UUID ?? '', profileType);
          const infoText = `Deleted ${deletedDocumentsCount} grant profiles from ATV)`;
          logger(infoText);

          await fillProfileForm(page, obj, obj.formPath, obj.formSelector);
          // ehkä tähän väliin pitää laittaa tapa testata tallennuksen onnistumista?
      }
    }

    // @ts-ignore
    if (successTest) {
      // We must delete here manually profiles, since we don't want to do this always.
      const deletedDocumentsCount = await deleteGrantsProfiles(process.env.TEST_USER_UUID ?? '', profileType);
      const infoText = `Deleted ${deletedDocumentsCount} grant profiles from ATV)`;
      logger(infoText, successTest.formSelector);

      await fillProfileForm(page, successTest, successTest.formPath, successTest.formSelector);
    }
  });

  test('Test Grants profile data', async () => {
    logger('Hakuprofiili');
    await page.goto("/fi/oma-asiointi/hakuprofiili");

    // @ts-ignore
    // await checkContactInfoPrivatePerson(page, profileDataPrivatePerson.success);

    // joko tässä tai sit tossa ylläolevassa funkkarissa vois tarkistaa myös,
    // että kaikki tallennetut kentät löytyy myös profiilista.

  });
})


test.afterAll(() => {
    // @ts-ignore
    const hasFailedTests = globalThis.testResults?.numFailedTests > 0;

    // tässä vois ehkä vielä ihan tarkistaa jostain, että profiili löytyy oikeesti atvsta..

    if (hasFailedTests) {
        logger('There were failed tests in this test file.');
        process.env.profileExistsRegistered = 'FALSE';
    } else {
        logger('All tests in this file passed.');
        process.env.profileExistsRegistered = 'TRUE';
    }
});



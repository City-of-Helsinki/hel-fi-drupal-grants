import {
  Page,
  test
} from '@playwright/test';


import {selectRole} from "../../utils/auth_helpers";

import {
  isProfileCreated,
} from '../../utils/profile_helpers';
import {
  fillProfileForm,
} from '../../utils/form_helpers'

import {
  profileDataPrivatePerson,
  FormData,
} from '../../utils/data/test_data'

import {TEST_USER_UUID} from '../../utils/data/test_data';
import {
  deleteGrantsProfiles
} from "../../utils/document_helpers";

// Define a type for testResults
type TestResults = {
  numFailedTests?: number;
  // Add other properties if needed
};

// Extend the globalThis type to include testResults
declare global {
  var testResults: TestResults | undefined;
}
const profileVariableName = 'profileCreatedPrivate';
const profileType = 'private_person';

test.describe('Private Person - Grants Profile', async () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    // page.locator = slowLocator(page, 500);

    await selectRole(page, 'PRIVATE_PERSON');
  });

  test.beforeEach(async () => {
    /*
    1. If you want to skip tests during test declaration, you should use test.skip() inside the test.describe() callback.
    2. If you want to skip tests during test execution, you should use test.skip() inside the beforeEach() hook.
    */
   const skip = await isProfileCreated(profileVariableName, profileType);
   console.log(skip);
    test.skip(skip);
  });

  // @ts-ignore
  test('Profile creation', async () => {
    const testDataArray: [string, FormData][] = Object.entries(profileDataPrivatePerson);
    let successTest: FormData;
    for (const [key, obj] of testDataArray) {

      if (key === 'success') {
        successTest = obj;
      } else {
        await (async () => {

          // We must delete here manually profiles, since we don't want to do this always.
          const deletedDocumentsCount = await deleteGrantsProfiles(TEST_USER_UUID, profileType);

          const infoText = `Deleted ${deletedDocumentsCount} grant profiles from ATV)`;
          console.log(infoText);

          await fillProfileForm(page, obj, obj.formPath, obj.formSelector);
          // ehkä tähän väliin pitää laittaa tapa testata tallennuksen onnistumista?
        })();
      }
    }

    // @ts-ignore
    if (successTest) {
      await (async () => {

        // We must delete here manually profiles, since we don't want to do this always.
        const deletedDocumentsCount = await deleteGrantsProfiles(TEST_USER_UUID, profileType);
        const infoText = `Deleted ${deletedDocumentsCount} grant profiles from ATV)`;
        console.log(infoText, successTest.formSelector);

        await fillProfileForm(page, successTest, successTest.formPath ?? '', successTest.formSelector);
      })();
    }
  })
})

test.afterAll(() => {
  // @ts-ignore
  const hasFailedTests = globalThis.testResults?.numFailedTests > 0;

  // tässä vois ehkä vielä ihan tarkistaa jostain, että profiili löytyy oikeesti atvsta..

  if (hasFailedTests) {
    console.log('There were failed tests in this test file.');
    process.env.profileExistsPrivate = 'FALSE';
  } else {
    console.log('All tests in this file passed.');
    process.env.profileExistsPrivate = 'TRUE';
  }
});


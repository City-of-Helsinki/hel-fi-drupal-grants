import {
  Page,
  test
} from '@playwright/test';
import {
  slowLocator
} from '../../utils/helpers';
import {existsSync, readFileSync} from 'fs';

import {AUTH_FILE_PATH, selectRole} from "../../utils/auth_helpers";

import {
  checkContactInfoPrivatePerson,
  runOrSkipTest

} from '../../utils/profile_helpers';
import {
  fillProfileForm,
} from '../../utils/form_helpers'

import {
  profileDataPrivatePerson,
  applicationData,
  FormData,
  PROFILE_FILE_PATH
} from '../../utils/data/test_data'

import {TEST_IBAN, TEST_USER_UUID} from '../../utils/data/test_data';
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


test.describe('Private Person - Oma Asiointi', () => {
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
});

test.describe('Private Person - Grants Profile', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    // page.locator = slowLocator(page, 500);

    await selectRole(page, 'PRIVATE_PERSON');
  });

// @ts-ignore
  const testDataArray: [string, FormData][] = Object.entries(profileDataPrivatePerson);
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
      }, profileVariableName);
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
    }, profileVariableName);


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


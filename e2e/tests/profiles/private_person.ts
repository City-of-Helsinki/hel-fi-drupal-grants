import {Locator, Page, expect, test} from '@playwright/test';
import {
  selectRole,
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

import {TEST_IBAN, TEST_USER_UUID} from '../../utils/data/test_data';
import {
  getAppEnvForATV,
  deleteGrantsProfiles
} from "../../utils/document_helpers";

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

  test.beforeEach( async() => {
    const deletedDocumentsCount = await deleteGrantsProfiles(TEST_USER_UUID);
    const infoText = `Deleted ${deletedDocumentsCount} grant profiles from ATV)`;
    console.log(infoText);

  })

  const testDataArray: [string, FormData][] = Object.entries(profileDataPrivatePerson);
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

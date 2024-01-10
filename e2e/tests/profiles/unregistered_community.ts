import {Page, test} from '@playwright/test';
import {logger} from "../../utils/logger";

import {
    runOrSkipProfileCreation
} from '../../utils/profile_helpers';
import {
  fillProfileForm,
} from '../../utils/form_helpers'

import {
    profileDataUnregisteredCommunity,
     FormData,
} from '../../utils/data/test_data'

import {TEST_USER_UUID} from '../../utils/data/test_data';
import {
  deleteGrantsProfiles
} from "../../utils/document_helpers";

import { selectRole } from "../../utils/auth_helpers";


const profileVariableName = 'profileCreatedUnregistered';
const profileType = 'unregistered_community';

test.describe('UNregistered Community - Grants Profile', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    // page.locator = slowLocator(page, 500);

    await selectRole(page, 'UNREGISTERED_COMMUNITY', 'new');
  });

    // @ts-ignore
    const testDataArray: [string, FormData][] = Object.entries(profileDataUnregisteredCommunity);
    let successTest: FormData;
    for (const [key, obj] of testDataArray) {

        if (key === 'success') {
            successTest = obj;
        } else {
            runOrSkipProfileCreation(`Testing...${obj.title}`, async () => {
// We must delete here manually profiles, since we don't want to do this always.
                const deletedDocumentsCount = await deleteGrantsProfiles(TEST_USER_UUID, profileType);
                const infoText = `Deleted ${deletedDocumentsCount} grant profiles from ATV)`;
                logger(infoText);


                await fillProfileForm(page, obj, obj.formPath, obj.formSelector);
                // ehkä tähän väliin pitää laittaa tapa testata tallennuksen onnistumista?
            }, profileVariableName, profileType);
        }
    }

    // @ts-ignore
    if (successTest) {
        runOrSkipProfileCreation(successTest.title, async () => {

            // We must delete here manually profiles, since we don't want to do this always.
            const deletedDocumentsCount = await deleteGrantsProfiles(TEST_USER_UUID, profileType);
            const infoText = `Deleted ${deletedDocumentsCount} grant profiles from ATV)`;
            logger(infoText, successTest.formSelector);

            await fillProfileForm(page, successTest, successTest.formPath ?? '', successTest.formSelector);
        }, profileVariableName, profileType);


    }


  test('Test Grants profile data', async () => {
    logger('Hakuprofiili');
    await page.goto("/fi/oma-asiointi/hakuprofiili");

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
        process.env.profileExistsPrivate = 'FALSE';
    } else {
        logger('All tests in this file passed.');
        process.env.profileExistsPrivate = 'TRUE';
    }
});



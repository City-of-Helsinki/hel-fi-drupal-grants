import {Page, expect, test} from '@playwright/test';
import {FormData, profileDataPrivatePerson} from "../../utils/data/test_data";
import {fillGrantsFormPage} from "../../utils/form_helpers";

import {
    privatePersonApplications as applicationData
} from "../../utils/data/application_data";
import {selectRole} from "../../utils/auth_helpers";
import {slowLocator, getObjectFromEnv} from "../../utils/helpers";
import {hideSlidePopup} from '../../utils/form_helpers'
import {validateSubmission} from "../../utils/validation_helpers";

const profileType = 'private_person';
const formId = '48';

const formPages = {
    "1_hakijan_tiedot": async (page: Page, formData:FormData, clickButton:Function) => {


        console.log('Hello!');
      },
}


test.describe('Private person KUVAPROJ(48)', () => {
    let page: Page;

    test.beforeAll(async ({browser}) => {
        page = await browser.newPage()

        page.locator = slowLocator(page, 10000);

        await selectRole(page, 'PRIVATE_PERSON');
    });

    // @ts-ignore
    const testDataArray: [string, FormData][] = Object.entries(applicationData[formId]);

    for (const [key, obj] of testDataArray) {

        test(`Form: ${obj.title}`, async () => {

            await hideSlidePopup(page);

            await fillGrantsFormPage(
                key,
                page,
                obj,
                obj.formPath,
                obj.formSelector,
                formId,
                profileType,
                formPages);
        });
    }

    for (const [key, obj] of testDataArray) {

        test(`Validate: ${obj.title}`, async () => {
            const storedata = getObjectFromEnv(profileType, formId);

            // expect(storedata).toBeDefined();

            console.log('Validate dubmissions', storedata);

            await validateSubmission(
                key,
                page,
                obj,
                storedata
            );

        });

    }

    for (const [key, obj] of testDataArray) {

        test(`Delete DRAFTS: ${obj.title}`, async () => {
            const storedata = getObjectFromEnv(profileType, formId);

            // expect(storedata).toBeDefined();

            console.log('Delete DRAFTS', storedata);

        });
    }


});

import {Page, expect, test} from '@playwright/test';
import {FormData, profileDataPrivatePerson} from "../../utils/data/test_data";
import {fillGrantsForm} from "../../utils/form_helpers";

import {
    privatePersonApplications as applicationData
} from "../../utils/data/application_data";
import {selectRole} from "../../utils/auth_helpers";
import {slowLocator, getObjectFromEnv} from "../../utils/helpers";
import {hideSlidePopup} from '../../utils/form_helpers'
import {validateSubmission} from "../../utils/validation_helpers";

const profileType = 'private_person';


test.describe('Verify submissions on this run', () => {
    let page: Page;

    test.beforeAll(async ({browser}) => {
        page = await browser.newPage()

        page.locator = slowLocator(page, 10000);

        await selectRole(page, 'PRIVATE_PERSON');
    });


    // @ts-ignore
    const testDataArray: [string, Object][] = Object.entries(applicationData);

    for (const [key, obj] of testDataArray) {
        const singleType: [string, FormData][] = Object.entries(obj);
        for (const [status, formData] of singleType) {
            test(`Validate: ${formData.title}`, async () => {
                console.log('Test data integrity');

            });
        }



    }

});

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
const formId = '48';


test.describe('Private person KUVAPROJ(48)', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    page.locator = slowLocator(page, 10000);

    await selectRole(page, 'PRIVATE_PERSON');
  });

  // @ts-ignore
  const testDataArray: [string, FormData][] = Object.entries(applicationData["48"]);

  for (const [key, obj] of testDataArray) {

    test(`Form: ${obj.title}`, async () => {

      await hideSlidePopup(page);

      await fillGrantsForm(
        key,
        page,
        obj,
        obj.formPath,
        obj.formSelector,
        formId,
        profileType);

    });

    test(`Validate: ${obj.title}`, async () => {
      const storedata = getObjectFromEnv(profileType, formId);

      expect(storedata).toBeDefined();

      await validateSubmission(
        key,
        page,
        obj,
        storedata
      );

    });


  }


});

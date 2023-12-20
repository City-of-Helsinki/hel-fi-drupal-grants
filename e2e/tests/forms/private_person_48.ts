import {Page, expect, test} from '@playwright/test';
import {FormData, profileDataPrivatePerson} from "../../utils/data/test_data";
import {fillGrantsForm} from "../../utils/form_helpers";

import {privatePersonApplications as applicationData} from "../../utils/data/application_data";
import {selectRole} from "../../utils/auth_helpers";
import {slowLocator} from "../../utils/helpers";

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
    test(`${obj.title}`, async () => {

      await page.$eval('#sliding-popup', (popup) => {
        // Set the 'display' property to 'none' to hide the element
        popup.style.display = 'none';
      });

      await fillGrantsForm(page, obj, obj.formPath, obj.formSelector);
      // ehkä tähän väliin pitää laittaa tapa testata tallennuksen onnistumista?

    });
  }



});

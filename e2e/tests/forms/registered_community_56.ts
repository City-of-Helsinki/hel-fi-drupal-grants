import {Page, test} from '@playwright/test';
import {FormData, PageHandlers, FormPage} from "../../utils/data/test_data";
import {fillHakijanTiedotRegisteredCommunity} from "../../utils/form_helpers";
import {fillFormField} from "../../utils/input_helpers";
import {generateTests} from "../../utils/test_generator_helpers";
import {Role, selectRole} from "../../utils/auth_helpers";
import {registeredCommunityApplications as applicationData} from '../../utils/data/application_data';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotRegisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-acting-year']) {
      await fillFormField(page, items['edit-acting-year'], 'edit-acting-year');
    }

    if (items['compensation-no']) {
      await page.locator('#edit-subventions')
        .getByText(items['compensation-no'].value ?? '').click();
  }

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    if (items['edit-compensation-purpose']) {
      await page.locator('#edit-compensation-purpose')
        .fill(items['edit-compensation-purpose'].value ?? '');
    }

  },
  'lisatiedot_ja_liitteet': async (page: Page, {items}: FormPage) => {

    if (items['edit-additional-information']) {
      await page.getByRole('textbox', {name: 'Lisätiedot'})
        .fill(items['edit-additional-information'].value ?? '');
    }

    if (items['edit-muu-liite']) {
      await fillFormField(page, items['edit-muu-liite'], 'edit-muu-liite')
    }

    if (items['edit-extra-info']) {
      await page.getByLabel('Lisäselvitys liitteistä')
        .fill(items['edit-extra-info'].value ?? '');
    }

  },
  'webform_preview': async (page: Page, {items}: FormPage) => {
    if (items['accept_terms_1']) {
      // Check data on confirmation page
      await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
    }
  },
};

test.describe('LIIKUNTAYLEIS(56)', () => {
  let page: Page;

  const profileType = 'registered_community';
  const formId = '56';

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage();
    await selectRole(page, profileType.toUpperCase() as Role);
  });

  test.afterAll(async() => {
    await page.close();
  });

  const testDataArray: [string, FormData][] = Object.entries(applicationData[formId]);
  const tests = generateTests(profileType, formId, formPages, testDataArray);

  for (const { testName, testFunction } of tests) {
    test(testName, async ({browser}) => {
      await testFunction(page, browser);
    });
  }
});

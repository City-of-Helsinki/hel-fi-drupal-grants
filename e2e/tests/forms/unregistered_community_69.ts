import {Browser, Page, test} from '@playwright/test';
import {FormData, PageHandlers, FormPage} from "../../utils/data/test_data";
import {fillHakijanTiedotUnregisteredCommunity,} from "../../utils/form_helpers";
import {fillFormField, uploadFile} from "../../utils/input_helpers";
import {generateTests} from "../../utils/test_generator_helpers";
import {Role, selectRole} from "../../utils/auth_helpers";
import {unRegisteredCommunityApplications as applicationData} from '../../utils/data/application_data';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotUnregisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-acting-year']) {
      await fillFormField(page, items['edit-acting-year'], 'edit-acting-year');
    }

    if (items['edit-jarjestimme-leireja-seuraavilla-alueilla']) {
      await fillFormField(page, items['edit-jarjestimme-leireja-seuraavilla-alueilla'], 'edit-jarjestimme-leireja-seuraavilla-alueilla)')
    }

  },
  '3_talousarvio': async (page: Page, {items}: FormPage) => {

    if (items['edit-tulo']) {
      await fillFormField(page, items['edit-tulo'], 'edit-tulo)')
    }

    if (items['edit-meno']) {
      await fillFormField(page, items['edit-meno'], 'edit-meno)')
    }

  },
  'lisatiedot_ja_liitteet': async (page: Page, {items}: FormPage) => {

    if (items['edit-additional-information']) {
      await page.getByRole('textbox', {name: 'Lisätiedot'})
        .fill(items['edit-additional-information'].value ?? '');
    }

    if (items['edit-yhteison-saannot-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-yhteison-saannot-attachment-upload'].selector?.value ?? '',
        items['edit-yhteison-saannot-attachment-upload'].selector?.resultValue ?? '',
        items['edit-yhteison-saannot-attachment-upload'].value
      )
    }

    if (items['edit-leiri-excel-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-leiri-excel-attachment-upload'].selector?.value ?? '',
        items['edit-leiri-excel-attachment-upload'].selector?.resultValue ?? '',
        items['edit-leiri-excel-attachment-upload'].value
      )
    }

    if (items['edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload'].selector?.value ?? '',
        items['edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload'].selector?.resultValue ?? '',
        items['edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload'].value
      )
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

test.describe('LEIRISELVITYS(69)', () => {
  let page: Page;
  let browser: Browser;

  const profileType = 'unregistered_community';
  const formId = '69';

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
    test(testName, async () => {
      await testFunction(page, browser);
    });
  }
});

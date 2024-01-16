import {Page, test} from '@playwright/test';
import {logger} from "../../utils/logger";
import {
  FormData,
  FormPage,
  PageHandlers,
} from '../../utils/data/test_data';
import {
  fillGrantsFormPage, fillHakijanTiedotRegisteredCommunity,
  hideSlidePopup
} from '../../utils/form_helpers';

import {
  registeredCommunityApplications as applicationData
} from '../../utils/data/application_data';
import {selectRole} from '../../utils/auth_helpers';
import {getObjectFromEnv} from '../../utils/helpers';
import {validateSubmission} from '../../utils/validation_helpers';

const profileType = 'registered_community';
const formId = '51';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotRegisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-acting-year']) {
      await page.locator('#edit-acting-year')
        .selectOption(items['edit-acting-year'].value ?? '');
    }

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    if (items['edit-compensation-purpose']) {
      await page.locator('#edit-compensation-purpose')
        .fill(items['edit-compensation-purpose'].value ?? '');
    }

    if (items['edit-benefits-loans']) {
      await page.locator('#edit-benefits-loans')
        .fill(items['edit-benefits-loans'].value ?? '');
    }

    if (items['edit-benefits-premises']) {
      await page.locator('#edit-benefits-premises')
        .fill(items['edit-benefits-premises'].value ?? '');
    }

    // Muut samaan tarkoitukseen myönnetyt avustukset puuttuu -> dynamicmultifield

  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-business-purpose']) {
      await page.locator('#edit-business-purpose')
        .fill(items['edit-business-purpose'].value ?? '');
    }

    if (items['edit-community-practices-business-1']) {
      await page.getByText('Ei', {exact: true})
        .click();
    }

    if (items['edit-fee-person']) {
      await page.locator('#edit-fee-person')
        .fill(items['edit-fee-person'].value ?? '');
    }

    if (items['edit-fee-community']) {
      await page.locator('#edit-fee-community')
        .fill(items['edit-fee-community'].value ?? '');
    }

    if (items['edit-members-applicant-person-global']) {
      await page.locator('#edit-members-applicant-person-global')
        .fill(items['edit-members-applicant-person-global'].value ?? '');
    }

    if (items['edit-members-applicant-person-local']) {
      await page.locator('#edit-members-applicant-person-local')
        .fill(items['edit-members-applicant-person-local'].value ?? '');
    }

    if (items['edit-members-applicant-community-global']) {
      await page.locator('#edit-members-applicant-community-global')
        .fill(items['edit-members-applicant-community-global'].value ?? '');
    }

    if (items['edit-members-applicant-community-local']) {
      await page.locator('#edit-members-applicant-community-local')
        .fill(items['edit-members-applicant-community-local'].value ?? '');
    }

  },
  'lisatiedot_ja_liitteet': async (page: Page, {items}: FormPage) => {

    if (items['edit-additional-information']) {
      await page.getByRole('textbox', {name: 'Lisätiedot'})
        .fill(items['edit-additional-information'].value ?? '');
    }

    if (items['edit-yhteison-saannot-isdeliveredlater']) {
      await page.getByRole('group', {name: 'Yhteisön säännöt'}).getByLabel('Liite toimitetaan myöhemmin').check();
    }

    if (items['edit-vahvistettu-tilinpaatos-isdeliveredlater']) {
      await page.getByRole('group', {name: 'Vahvistettu tilinpäätös'}).getByLabel('Liite toimitetaan myöhemmin').check();
    }

    if (items['edit-vahvistettu-toimintakertomus-isdeliveredlater']) {
      await page.getByRole('group', {name: 'Vahvistettu toimintakertomus'}).getByLabel('Liite toimitetaan myöhemmin').check();
    }

    if (items['edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-isdeliveredlater']) {
      await page.getByRole('group', {name: 'Vahvistettu tilin- tai toiminnantarkastuskertomus'}).getByLabel('Liite toimitetaan myöhemmin').check();
    }

    if (items['edit-vuosikokouksen-poytakirja-isdeliveredlater']) {
      await page.locator('#edit-vuosikokouksen-poytakirja--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    }

    if (items['edit-toimintasuunnitelma-isdeliveredlater']) {
      await page.locator('#edit-toimintasuunnitelma--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    }

    if (items['edit-talousarvio-isdeliveredlater']) {
      await page.locator('#edit-talousarvio--wrapper').getByText('Liite toimitetaan myöhemmin').click();
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

test.describe('KASKOYLEIS(51)', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    await selectRole(page, 'REGISTERED_COMMUNITY');
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

      logger('Delete DRAFTS', storedata, key);

    });
  }


});

import {Page, test} from '@playwright/test';
import {FormData, PageHandlers, FormPage} from "../../utils/data/test_data";
import {fillGrantsFormPage, fillHakijanTiedotRegisteredCommunity,} from "../../utils/form_helpers";
import {selectRole} from "../../utils/auth_helpers";
import {getObjectFromEnv} from "../../utils/env_helpers";
import {validateSubmission} from "../../utils/validation_helpers";
import {deleteDraftApplication} from "../../utils/deletion_helpers";
import {copyApplication} from "../../utils/copying_helpers";
import {fillFormField, fillInputField, uploadFile} from "../../utils/input_helpers";
import {registeredCommunityApplications as applicationData} from '../../utils/data/application_data';
import {swapFieldValues} from "../../utils/field_swap_helpers";

const profileType = 'registered_community';
const formId = '61';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotRegisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-acting-year']) {
      await fillFormField(page, items['edit-acting-year'], 'edit-acting-year');
    }

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    if (items['edit-compensation-purpose']) {
      await page.locator('#edit-compensation-purpose')
        .fill(items['edit-compensation-purpose'].value ?? '');
    }

    if (items['edit-myonnetty-avustus']) {
      await fillFormField(page, items['edit-myonnetty-avustus'], 'edit-myonnetty-avustus')
    }

    if (items['edit-benefits-loans']) {
      await page.locator('#edit-benefits-loans')
        .fill(items['edit-benefits-loans'].value ?? '');
    }

    if (items['edit-benefits-premises']) {
      await page.locator('#edit-benefits-premises')
        .fill(items['edit-benefits-premises'].value ?? '');
    }

    if (items['edit-compensation-boolean-1']) {
      await page.locator('#edit-compensation-boolean')
        .getByText(items['edit-compensation-boolean-1'].value ?? '').click();
    }

    if (items['edit-compensation-explanation']) {
      await page.locator('#edit-compensation-explanation')
        .fill(items['edit-compensation-explanation'].value ?? '');
    }

  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-business-purpose']) {
      await page.locator('#edit-business-purpose')
        .fill(items['edit-business-purpose'].value ?? '');
    }

    if (items['edit-community-practices-business-0']) {
      await page.locator('#edit-community-practices-business')
        .getByText(items['edit-community-practices-business-0'].value ?? '').click();
    }

    await fillInputField(
      items['edit-fee-person'].value ?? '',
      items['edit-fee-person'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-fee-person',
      },
      page,
      'edit-fee-person'
    );

    await fillInputField(
      items['edit-fee-community'].value ?? '',
      items['edit-fee-community'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-fee-community',
      },
      page,
      'edit-fee-community'
    );

    await fillInputField(
      items['edit-members-applicant-person-global'].value ?? '',
      items['edit-members-applicant-person-global'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-members-applicant-person-global',
      },
      page,
      'edit-members-applicant-person-global'
    );

    await fillInputField(
      items['edit-members-applicant-person-local'].value ?? '',
      items['edit-members-applicant-person-local'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-members-applicant-person-local',
      },
      page,
      'edit-members-applicant-person-local'
    );

    await fillInputField(
      items['edit-members-applicant-community-global'].value ?? '',
      items['edit-members-applicant-community-global'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-members-applicant-community-global',
      },
      page,
      'edit-members-applicant-community-global'
    );

    await fillInputField(
      items['edit-members-applicant-community-local'].value ?? '',
      items['edit-members-applicant-community-local'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-members-applicant-community-local',
      },
      page,
      'edit-members-applicant-community-local'
    );

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

    if (items['edit-vahvistettu-tilinpaatos-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-vahvistettu-tilinpaatos-attachment-upload'].selector?.value ?? '',
        items['edit-vahvistettu-tilinpaatos-attachment-upload'].selector?.resultValue ?? '',
        items['edit-vahvistettu-tilinpaatos-attachment-upload'].value
      )
    }

    if (items['edit-vahvistettu-toimintakertomus-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-vahvistettu-toimintakertomus-attachment-upload'].selector?.value ?? '',
        items['edit-vahvistettu-toimintakertomus-attachment-upload'].selector?.resultValue ?? '',
        items['edit-vahvistettu-toimintakertomus-attachment-upload'].value
      )
    }

    if (items['edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload'].selector?.value ?? '',
        items['edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload'].selector?.resultValue ?? '',
        items['edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload'].value
      )
    }

    if (items['edit-vuosikokouksen-poytakirja-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-vuosikokouksen-poytakirja-attachment-upload'].selector?.value ?? '',
        items['edit-vuosikokouksen-poytakirja-attachment-upload'].selector?.resultValue ?? '',
        items['edit-vuosikokouksen-poytakirja-attachment-upload'].value
      )
    }

    if (items['edit-toimintasuunnitelma-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-toimintasuunnitelma-attachment-upload'].selector?.value ?? '',
        items['edit-toimintasuunnitelma-attachment-upload'].selector?.resultValue ?? '',
        items['edit-toimintasuunnitelma-attachment-upload'].value
      )
    }

    if (items['edit-talousarvio-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-talousarvio-attachment-upload'].selector?.value ?? '',
        items['edit-talousarvio-attachment-upload'].selector?.resultValue ?? '',
        items['edit-talousarvio-attachment-upload'].value
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

test.describe('YMPARISTOYLEIS(61)', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    await selectRole(page, 'REGISTERED_COMMUNITY');
  });

  const testDataArray: [string, FormData][] = Object.entries(applicationData[formId]);

  for (const [key, obj] of testDataArray) {
    test(`Form: ${obj.title}`, async () => {
      await fillGrantsFormPage(
        key,
        page,
        obj,
        obj.formPath,
        obj.formSelector,
        formId,
        profileType,
        formPages
      );
    });
  }

  for (const [key, obj] of testDataArray) {
    if (!obj.testFormCopying) continue;
    test(`Copy form: ${obj.title}`, async () => {
      const storedata = getObjectFromEnv(profileType, formId);
      await copyApplication(
        key,
        profileType,
        formId,
        page,
        obj,
        storedata
      );
    });
  }

  for (const [key, obj] of testDataArray) {
    if (!obj.testFieldSwap) continue;
    test(`Field swap: ${obj.title}`, async () => {
      const storedata = getObjectFromEnv(profileType, formId);
      await swapFieldValues(
        key,
        page,
        obj,
        storedata
      );
    });
  }

  for (const [key, obj] of testDataArray) {
    if (obj.viewPageSkipValidation || obj.testFormCopying || obj.testFieldSwap) continue;
    test(`Validate: ${obj.title}`, async () => {
      const storedata = getObjectFromEnv(profileType, formId);
      await validateSubmission(
        key,
        page,
        obj,
        storedata
      );
    });
  }

  for (const [key, obj] of testDataArray) {
    test(`Delete drafts: ${obj.title}`, async () => {
      const storedata = getObjectFromEnv(profileType, formId);
      await deleteDraftApplication(
        key,
        page,
        obj,
        storedata
      );
    });
  }

});

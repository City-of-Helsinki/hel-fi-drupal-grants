import {Page, test} from '@playwright/test';
import {FormData, PageHandlers, FormPage} from "../../utils/data/test_data";
import {fillGrantsFormPage, fillHakijanTiedotUnregisteredCommunity,} from "../../utils/form_helpers";
import {selectRole} from "../../utils/auth_helpers";
import {getObjectFromEnv} from "../../utils/env_helpers";
import {validateSubmission} from "../../utils/validation_helpers";
import {deleteDraftApplication} from "../../utils/deletion_helpers";
import {copyApplication} from "../../utils/copying_helpers";
import {fillFormField, fillInputField, uploadFile} from "../../utils/input_helpers";
import {unRegisteredCommunityApplications as applicationData} from '../../utils/data/application_data';
import {swapFieldValues} from "../../utils/field_swap_helpers";

const profileType = 'unregistered_community';
const formId = '62';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotUnregisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-kenelle-haen-avustusta']) {
      await page.locator('#edit-kenelle-haen-avustusta')
        .selectOption(items['edit-kenelle-haen-avustusta'].value ?? '');
    }

    if (items['edit-acting-year']) {
      await page.locator('#edit-acting-year')
        .selectOption(items['edit-acting-year'].value ?? '');
    }

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    if (items['edit-myonnetty-avustus']) {
      await fillFormField(page, items['edit-myonnetty-avustus'], 'edit-myonnetty-avustus')
    }

    if (items['edit-haettu-avustus-tieto']) {
      await fillFormField(page, items['edit-haettu-avustus-tieto'], 'edit-haettu-avustus-tieto')
    }

  },
  '3_jasenet_tai_aktiiviset_osallistujat': async (page: Page, {items}: FormPage) => {

    for (const [itemKey, item]
      of Object.entries(items)) {
      await fillInputField(
        item.value ?? '',
        item.selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: itemKey,
        },
        page,
        itemKey
      );
    }

  },
  'projektisuunnitelma': async (page: Page, {items}: FormPage) => {

    if (items['edit-projektin-nimi']) {
      await page.locator('#edit-projektin-nimi')
        .fill(items['edit-projektin-nimi'].value ?? '');
    }

    if (items['edit-projektin-tavoitteet']) {
      await page.locator('#edit-projektin-tavoitteet')
        .fill(items['edit-projektin-tavoitteet'].value ?? '');
    }

    if (items['edit-projektin-sisalto']) {
      await page.locator('#edit-projektin-sisalto')
        .fill(items['edit-projektin-sisalto'].value ?? '');
    }

    if (items['edit-projekti-alkaa']) {
      await page.locator('#edit-projekti-alkaa')
        .fill(items['edit-projekti-alkaa'].value ?? '');
    }

    if (items['edit-projekti-loppuu']) {
      await page.locator('#edit-projekti-loppuu')
        .fill(items['edit-projekti-loppuu'].value ?? '');
    }

    if (items['edit-osallistujat-7-28']) {
      await fillInputField(
        items['edit-osallistujat-7-28'].value ?? '',
        items['edit-osallistujat-7-28'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-osallistujat-7-28',
        },
        page,
        'edit-osallistujat-7-28'
      );
    }

    if (items['edit-osallistujat-kaikki']) {
      await fillInputField(
        items['edit-osallistujat-kaikki'].value ?? '',
        items['edit-osallistujat-kaikki'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-osallistujat-kaikki',
        },
        page,
        'edit-osallistujat-kaikki'
      );
    }

    if (items['edit-projektin-paikka-2']) {
      await page.locator('#edit-projektin-paikka-2')
        .fill(items['edit-projektin-paikka-2'].value ?? '');
    }

  },
  '6_talous': async (page: Page, {items}: FormPage) => {

    if (items['edit-omarahoitusosuuden-kuvaus']) {
      await fillInputField(
        items['edit-omarahoitusosuuden-kuvaus'].value ?? '',
        items['edit-omarahoitusosuuden-kuvaus'].selector ?? {
          type: 'data-drupal-selector',
          name: 'data-drupal-selector',
          value: 'edit-omarahoitusosuuden-kuvaus',
        },
        page,
        'edit-omarahoitusosuuden-kuvaus'
      );
    }

    if (items['edit-omarahoitusosuus']) {
      await fillInputField(
        items['edit-omarahoitusosuus'].value ?? '',
        items['edit-omarahoitusosuus'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-omarahoitusosuus',
        },
        page,
        'edit-omarahoitusosuus'
      );
    }

    if (items['edit-budget-other-income']) {
      await fillFormField(page, items['edit-budget-other-income'], 'edit-budget-other-income')
    }

    if (items['edit-budget-other-cost']) {
      await fillFormField(page, items['edit-budget-other-cost'], 'edit-budget-other-cost')
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

    if (items['edit-projektisuunnitelma-liite-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-projektisuunnitelma-liite-attachment-upload'].selector?.value ?? '',
        items['edit-projektisuunnitelma-liite-attachment-upload'].selector?.resultValue ?? '',
        items['edit-projektisuunnitelma-liite-attachment-upload'].value
      )
    }

    if (items['edit-projektin-talousarvio-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-projektin-talousarvio-attachment-upload'].selector?.value ?? '',
        items['edit-projektin-talousarvio-attachment-upload'].selector?.resultValue ?? '',
        items['edit-projektin-talousarvio-attachment-upload'].value
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

test.describe('NUORPROJ(62)', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    await selectRole(page, 'UNREGISTERED_COMMUNITY');
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

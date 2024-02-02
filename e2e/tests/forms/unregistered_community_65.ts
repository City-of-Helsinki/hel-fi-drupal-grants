import {Page, test} from '@playwright/test';
import {
  FormData,
  FormPage,
  PageHandlers,
} from '../../utils/data/test_data';
import {
  fillGrantsFormPage, fillHakijanTiedotUnregisteredCommunity, fillInputField,
  hideSlidePopup, uploadFile
} from '../../utils/form_helpers';

import {
  unRegisteredCommunityApplications as applicationData
} from '../../utils/data/application_data';
import {selectRole} from '../../utils/auth_helpers';
import {getObjectFromEnv} from '../../utils/helpers';
import {validateSubmission} from '../../utils/validation_helpers';

const profileType = 'unregistered_community';
const formId = '65';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotUnregisteredCommunity(items, page);
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

  },
  '3_talousarvio': async (page: Page, {items}: FormPage) => {

    if (items['edit-tulo-items-0-item-label']) {
      await page.locator('#edit-tulo-items-0-item-label')
        .fill(items['edit-tulo-items-0-item-label'].value ?? '');
    }

    if (items['edit-tulo-items-0-item-value']) {
      await page.locator('#edit-tulo-items-0-item-value')
        .fill(items['edit-tulo-items-0-item-value'].value ?? '');
    }

    if (items['edit-meno-items-0-item-label']) {
      await page.locator('#edit-meno-items-0-item-label')
        .fill(items['edit-meno-items-0-item-label'].value ?? '');
    }

    if (items['edit-meno-items-0-item-value']) {
      await page.locator('#edit-meno-items-0-item-value')
        .fill(items['edit-meno-items-0-item-value'].value ?? '');
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

    if (items['edit-muu-liite-items-0-item-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-muu-liite-items-0-item-attachment-upload'].selector?.value ?? '',
        items['edit-muu-liite-items-0-item-attachment-upload'].selector?.resultValue ?? '',
        items['edit-muu-liite-items-0-item-attachment-upload'].value
      )
    }

    if (items['edit-muu-liite-items-0-item-description']) {
      await fillInputField(
        items['edit-muu-liite-items-0-item-description'].value ?? '',
        items['edit-muu-liite-items-0-item-description'].selector ?? {
          type: 'data-drupal-selector',
          name: 'data-drupal-selector',
          value: 'edit-muu-liite-items-0-item-description',
        },
        page,
        'edit-muu-liite-items-0-item-description'
      );
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


test.describe('NUORLOMALEIR(65)', () => {
  let page: Page;

    test.beforeAll(async ({browser}) => {
        page = await browser.newPage()
        await selectRole(page, 'UNREGISTERED_COMMUNITY');
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
    if (obj.viewPageSkipValidation) continue;
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

            console.log('Delete DRAFTS', storedata, key);

        });
    }


});

import {Page, test} from '@playwright/test';
import {logger} from "../../utils/logger";
import {
  FormData,
  FormPage,
  PageHandlers,
} from '../../utils/data/test_data';
import {
  fillFormField,
  fillGrantsFormPage, fillHakijanTiedotRegisteredCommunity, fillInputField,
  hideSlidePopup, uploadFile
} from '../../utils/form_helpers';

import {
  registeredCommunityApplications as applicationData
} from '../../utils/data/application_data';
import {selectRole} from '../../utils/auth_helpers';
import {getObjectFromEnv} from '../../utils/helpers';
import {validateSubmission} from '../../utils/validation_helpers';
import {deleteDraftApplication} from "../../utils/deletion_helpers";
import {copyApplication} from "../../utils/copying_helpers";

const profileType = 'registered_community';
const formId = '63';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotRegisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-acting-year']) {
      await page.locator('#edit-acting-year')
        .selectOption(items['edit-acting-year'].value ?? '');
    }

    if (items['edit-subventions-items-1-amount']) {
      await page.locator('#edit-subventions-items-1-amount')
        .fill(items['edit-subventions-items-1-amount'].value ?? '');
    }

    if (items['edit-haen-vuokra-avustusta-1']) {
      await page.locator('#edit-haen-vuokra-avustusta')
        .getByText(items['edit-haen-vuokra-avustusta-1'].value ?? '').click();
    }

    if (items['edit-myonnetty-avustus']) {
      await fillFormField(page, items['edit-myonnetty-avustus'], 'edit-myonnetty-avustus')
    }

    if (items['edit-haettu-avustus-tieto']) {
      await fillFormField(page, items['edit-haettu-avustus-tieto'], 'edit-haettu-avustus-tieto')
    }

  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-jasenet-0-6-vuotiaat']) {
      await fillInputField(
        items['edit-jasenet-0-6-vuotiaat'].value ?? '',
        items['edit-jasenet-0-6-vuotiaat'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-jasenet-0-6-vuotiaat',
        },
        page,
        'edit-jasenet-0-6-vuotiaat'
      );
    }

    if (items['edit-0-6-joista-helsinkilaisia']) {
      await fillInputField(
        items['edit-0-6-joista-helsinkilaisia'].value ?? '',
        items['edit-0-6-joista-helsinkilaisia'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-0-6-joista-helsinkilaisia',
        },
        page,
        'edit-0-6-joista-helsinkilaisia'
      );
    }

    if (items['edit-jasenet-7-28-vuotiaat']) {
      await fillInputField(
        items['edit-jasenet-7-28-vuotiaat'].value ?? '',
        items['edit-jasenet-7-28-vuotiaat'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-jasenet-7-28-vuotiaat',
        },
        page,
        'edit-jasenet-7-28-vuotiaat'
      );
    }

    if (items['edit-7-28-joista-helsinkilaisia']) {
      await fillInputField(
        items['edit-7-28-joista-helsinkilaisia'].value ?? '',
        items['edit-7-28-joista-helsinkilaisia'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-7-28-joista-helsinkilaisia',
        },
        page,
        'edit-7-28-joista-helsinkilaisia'
      );
    }

    if (items['edit-muut-jasenet-tai-aktiiviset-osallistujat']) {
      await fillInputField(
        items['edit-muut-jasenet-tai-aktiiviset-osallistujat'].value ?? '',
        items['edit-muut-jasenet-tai-aktiiviset-osallistujat'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-jasenet-tai-aktiiviset-osallistujat',
        },
        page,
        'edit-muut-jasenet-tai-aktiiviset-osallistujat'
      );
    }

    if (items['edit-muut-joista-helsinkilaisia']) {
      await fillInputField(
        items['edit-muut-joista-helsinkilaisia'].value ?? '',
        items['edit-muut-joista-helsinkilaisia'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-joista-helsinkilaisia',
        },
        page,
        'edit-muut-joista-helsinkilaisia'
      );
    }

    if (items['edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter']) {
      await fillInputField(
        items['edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter'].value ?? '',
        items['edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter',
        },
        page,
        'edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter'
      );
    }

    if (items['edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli']) {
      await fillInputField(
        items['edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli'].value ?? '',
        items['edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli',
        },
        page,
        'edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli'
      );
    }

    if (items['edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-0']) {
      await page.locator('#edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa')
        .getByText(items['edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-0'].value ?? '').click();
    }

    if (items['edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa']) {
      await fillFormField(page, items['edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa'], 'edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa')
    }

    if (items['edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa']) {
      await fillFormField(page, items['edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa'], 'edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa')
    }

    if (items['edit-miten-nuoret-osallistuvat-yhdistyksen-toiminnan-suunnitteluun-ja']) {
      await page.locator('#edit-miten-nuoret-osallistuvat-yhdistyksen-toiminnan-suunnitteluun-ja')
        .fill(items['edit-miten-nuoret-osallistuvat-yhdistyksen-toiminnan-suunnitteluun-ja'].value ?? '');
    }

  },
  '4_palkkaustiedot': async (page: Page, {items}: FormPage) => {

    // Loop items, all have selectors defined so we can use looping.
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
  'vuokra_avustushakemuksen_tiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-vuokratun-tilan-tiedot']) {
      await fillFormField(page, items['edit-vuokratun-tilan-tiedot'], 'edit-vuokratun-tilan-tiedot')
    }

    if (items['edit-lisatiedot']) {
      await fillInputField(
        items['edit-lisatiedot'].value ?? '',
        items['edit-lisatiedot'].selector ?? {
          type: 'data-drupal-selector',
          name: 'data-drupal-selector',
          value: 'edit-lisatiedot',
        },
        page,
        'edit-lisatiedot'
      );
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

test.describe('NUORTOIMPALKKA(63)', () => {
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
    if (!obj.isCopyForm) continue;
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
    if (obj.viewPageSkipValidation || obj.isCopyForm) continue;
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

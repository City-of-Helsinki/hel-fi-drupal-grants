import {Page, test} from '@playwright/test';
import {
  FormData,
  FormPage,
  PageHandlers,
} from '../../utils/data/test_data';
import {
  fillGrantsFormPage, fillHakijanTiedotRegisteredCommunity, fillInputField,
  hideSlidePopup, uploadFile
} from '../../utils/form_helpers';

import {
  registeredCommunityApplications as applicationData
} from '../../utils/data/application_data';
import {selectRole} from '../../utils/auth_helpers';
import {getObjectFromEnv} from '../../utils/helpers';
import {validateSubmission} from '../../utils/validation_helpers';

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
        .getByText('Kyllä').click();
    }

    // muut samaan tarkoitukseen myönnetyt
    // muut samaan tarkoitukseen haetut

  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-jasenet-0-6-vuotiaat']) {
      await page.locator('#edit-jasenet-0-6-vuotiaat')
        .fill(items['edit-jasenet-0-6-vuotiaat'].value ?? '');
    }

    if (items['edit-0-6-joista-helsinkilaisia']) {
      await page.locator('#edit-0-6-joista-helsinkilaisia')
        .fill(items['edit-0-6-joista-helsinkilaisia'].value ?? '');
    }

    if (items['edit-jasenet-7-28-vuotiaat']) {
      await page.locator('#edit-jasenet-7-28-vuotiaat')
        .fill(items['edit-jasenet-7-28-vuotiaat'].value ?? '');
    }

    if (items['edit-7-28-joista-helsinkilaisia']) {
      await page.locator('#edit-7-28-joista-helsinkilaisia')
        .fill(items['edit-7-28-joista-helsinkilaisia'].value ?? '');
    }

    if (items['edit-muut-jasenet-tai-aktiiviset-osallistujat']) {
      await page.locator('#edit-muut-jasenet-tai-aktiiviset-osallistujat')
        .fill(items['edit-muut-jasenet-tai-aktiiviset-osallistujat'].value ?? '');
    }

    if (items['edit-muut-joista-helsinkilaisia']) {
      await page.locator('#edit-muut-joista-helsinkilaisia')
        .fill(items['edit-muut-joista-helsinkilaisia'].value ?? '');
    }

    if (items['edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter']) {
      await page.locator('#edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter')
        .fill(items['edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter'].value ?? '');
    }

    if (items['edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli']) {
      await page.locator('#edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli')
        .fill(items['edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli'].value ?? '');
    }

    if (items['edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-1']) {
      await page.locator('#edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa')
        .getByText('Kyllä').click();
    }

    if (items['edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa-items-0-item-organizationname']) {
      await page.locator('#edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa-items-0-item-organizationname')
        .fill(items['edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa-items-0-item-organizationname'].value ?? '');
    }

    if (items['edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa-items-0-item-fee']) {
      await page.locator('#edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa-items-0-item-fee')
        .fill(items['edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa-items-0-item-fee'].value ?? '');
    }

    if (items['edit-miten-nuoret-osallistuvat-yhdistyksen-toiminnan-suunnitteluun-ja']) {
      await page.locator('#edit-miten-nuoret-osallistuvat-yhdistyksen-toiminnan-suunnitteluun-ja')
        .fill(items['edit-miten-nuoret-osallistuvat-yhdistyksen-toiminnan-suunnitteluun-ja'].value ?? '');
    }

  },
  '4_palkkaustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-kuinka-monta-paatoimista-palkattua-tyontekijaa-yhdistyksessa-tyo']) {
      await page.locator('#edit-kuinka-monta-paatoimista-palkattua-tyontekijaa-yhdistyksessa-tyo')
        .fill(items['edit-kuinka-monta-paatoimista-palkattua-tyontekijaa-yhdistyksessa-tyo'].value ?? '');
    }

    if (items['edit-palkkauskulut']) {
      await page.locator('#edit-palkkauskulut')
        .fill(items['edit-palkkauskulut'].value ?? '');
    }

    if (items['edit-lakisaateiset-ja-vapaaehtoiset-henkilosivukulut']) {
      await page.locator('#edit-lakisaateiset-ja-vapaaehtoiset-henkilosivukulut')
        .fill(items['edit-lakisaateiset-ja-vapaaehtoiset-henkilosivukulut'].value ?? '');
    }

    if (items['edit-matka-ja-koulutuskulut']) {
      await page.locator('#edit-matka-ja-koulutuskulut')
        .fill(items['edit-matka-ja-koulutuskulut'].value ?? '');
    }

  },
  'vuokra_avustushakemuksen_tiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-vuokratun-tilan-tiedot-items-0-item-premiseaddress']) {
      await page.locator('#edit-vuokratun-tilan-tiedot-items-0-item-premiseaddress')
        .fill(items['edit-vuokratun-tilan-tiedot-items-0-item-premiseaddress'].value ?? '');
    }

    if (items['edit-vuokratun-tilan-tiedot-items-0-item-premisepostalcode']) {
      await page.locator('#edit-vuokratun-tilan-tiedot-items-0-item-premisepostalcode')
        .fill(items['edit-vuokratun-tilan-tiedot-items-0-item-premisepostalcode'].value ?? '');
    }

    if (items['edit-vuokratun-tilan-tiedot-items-0-item-premisepostoffice']) {
      await page.locator('#edit-vuokratun-tilan-tiedot-items-0-item-premisepostoffice')
        .fill(items['edit-vuokratun-tilan-tiedot-items-0-item-premisepostoffice'].value ?? '');
    }

    if (items['edit-vuokratun-tilan-tiedot-items-0-item-rentsum']) {
      await page.locator('#edit-vuokratun-tilan-tiedot-items-0-item-rentsum')
        .fill(items['edit-vuokratun-tilan-tiedot-items-0-item-rentsum'].value ?? '');
    }

    if (items['edit-vuokratun-tilan-tiedot-items-0-item-lessorname']) {
      await page.locator('#edit-vuokratun-tilan-tiedot-items-0-item-lessorname')
        .fill(items['edit-vuokratun-tilan-tiedot-items-0-item-lessorname'].value ?? '');
    }

    if (items['edit-vuokratun-tilan-tiedot-items-0-item-lessorphoneoremail']) {
      await page.locator('#edit-vuokratun-tilan-tiedot-items-0-item-lessorphoneoremail')
        .fill(items['edit-vuokratun-tilan-tiedot-items-0-item-lessorphoneoremail'].value ?? '');
    }

    if (items['edit-vuokratun-tilan-tiedot-items-0-item-usage']) {
      await page.locator('#edit-vuokratun-tilan-tiedot-items-0-item-usage')
        .fill(items['edit-vuokratun-tilan-tiedot-items-0-item-usage'].value ?? '');
    }

    if (items['edit-vuokratun-tilan-tiedot-items-0-item-daysperweek']) {
      await page.locator('#edit-vuokratun-tilan-tiedot-items-0-item-daysperweek')
        .fill(items['edit-vuokratun-tilan-tiedot-items-0-item-daysperweek'].value ?? '');
    }

    if (items['edit-vuokratun-tilan-tiedot-items-0-item-hoursperday']) {
      await page.locator('#edit-vuokratun-tilan-tiedot-items-0-item-hoursperday')
        .fill(items['edit-vuokratun-tilan-tiedot-items-0-item-hoursperday'].value ?? '');
    }

    if (items['edit-lisatiedot']) {
      await page.locator('#edit-lisatiedot')
        .fill(items['edit-lisatiedot'].value ?? '');
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


test.describe('NUORTOIMPALKKA(63)', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    // page.locator = slowLocator(page, 10000);

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

      console.log('Delete DRAFTS', storedata);

    });
  }


});

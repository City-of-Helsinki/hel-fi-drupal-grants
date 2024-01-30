import {Page, test} from '@playwright/test';
import {logger} from "../../utils/logger";
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
const formId = '60';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotRegisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-hakijan-tyyppi']) {
      await page.locator('#edit-hakijan-tyyppi')
        .selectOption(items['edit-hakijan-tyyppi'].value ?? '');
    }

    if (items['edit-acting-year']) {
      await page.locator('#edit-acting-year')
        .selectOption(items['edit-acting-year'].value ?? '');
    }

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    if (items['edit-subventions-items-1-amount']) {
      await page.locator('#edit-subventions-items-1-amount')
        .fill(items['edit-subventions-items-1-amount'].value ?? '');
    }

    if (items['edit-compensation-boolean-1']) {
      await page.locator('#edit-compensation-boolean')
        .getByText(items['edit-compensation-boolean-1'].value ?? '').click();
    }

    if (items['edit-compensation-explanation']) {
      await page.locator('#edit-compensation-explanation')
        .fill(items['edit-compensation-explanation'].value ?? '');
    }

    if (items['edit-compensation-purpose']) {
      await page.locator('#edit-compensation-purpose')
        .fill(items['edit-compensation-purpose'].value ?? '');
    }

    // muut samaan tarkoitukseen myönnetyt

    if (items['edit-tuntimaara-yhteensa']) {
      await page.locator('#edit-tuntimaara-yhteensa')
        .fill(items['edit-tuntimaara-yhteensa'].value ?? '');
    }

    if (items['edit-vuokrat-yhteensa']) {
      await page.locator('#edit-vuokrat-yhteensa')
        .fill(items['edit-vuokrat-yhteensa'].value ?? '');
    }

    if (items['edit-seuraavalle-vuodelle-suunniteltu-muutos-tilojen-kaytossa-tunnit-']) {
      await page.locator('#edit-seuraavalle-vuodelle-suunniteltu-muutos-tilojen-kaytossa-tunnit-')
        .fill(items['edit-seuraavalle-vuodelle-suunniteltu-muutos-tilojen-kaytossa-tunnit-'].value ?? '');
    }

    if (items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-premisename']) {
      await page.locator('#edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-premisename')
        .fill(items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-premisename'].value ?? '');
    }

    if (items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-datebegin']) {
      await page.locator('#edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-datebegin')
        .fill(items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-datebegin'].value ?? '');
    }

    if (items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-dateend']) {
      await page.locator('#edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-dateend')
        .fill(items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-dateend'].value ?? '');
    }

    if (items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-tenantname']) {
      await page.locator('#edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-tenantname')
        .fill(items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-tenantname'].value ?? '');
    }

    if (items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-hours']) {
      await page.locator('#edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-hours')
        .fill(items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-hours'].value ?? '');
    }

    if (items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-sum']) {
      await page.locator('#edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-sum')
        .fill(items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-0-item-sum'].value ?? '');
    }

  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-miehet-20-63-vuotiaat-aktiiviharrastajat']) {
      await page.locator('#edit-miehet-20-63-vuotiaat-aktiiviharrastajat')
        .fill(items['edit-miehet-20-63-vuotiaat-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-joista-helsinkilaisia-miehet-20-63-aktiiviharrastajat']) {
      await page.locator('#edit-joista-helsinkilaisia-miehet-20-63-aktiiviharrastajat')
        .fill(items['edit-joista-helsinkilaisia-miehet-20-63-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-naiset-20-63-vuotiaat-aktiiviharrastajat']) {
      await page.locator('#edit-naiset-20-63-vuotiaat-aktiiviharrastajat')
        .fill(items['edit-naiset-20-63-vuotiaat-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-joista-helsinkilaisia-naiset-20-63-aktiiviharrastajat']) {
      await page.locator('#edit-joista-helsinkilaisia-naiset-20-63-aktiiviharrastajat')
        .fill(items['edit-joista-helsinkilaisia-naiset-20-63-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-muut-20-63-vuotiaat-aktiiviharrastajat']) {
      await page.locator('#edit-muut-20-63-vuotiaat-aktiiviharrastajat')
        .fill(items['edit-muut-20-63-vuotiaat-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-joista-helsinkilaisia-muut-20-63-aktiiviharrastajat']) {
      await page.locator('#edit-joista-helsinkilaisia-muut-20-63-aktiiviharrastajat')
        .fill(items['edit-joista-helsinkilaisia-muut-20-63-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-miehet-64-aktiiviharrastajat']) {
      await page.locator('#edit-miehet-64-aktiiviharrastajat')
        .fill(items['edit-miehet-64-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-joista-helsinkilaisia-miehet-64-aktiiviharrastajat']) {
      await page.locator('#edit-joista-helsinkilaisia-miehet-64-aktiiviharrastajat')
        .fill(items['edit-joista-helsinkilaisia-miehet-64-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-naiset-64-aktiiviharrastajat']) {
      await page.locator('#edit-naiset-64-aktiiviharrastajat')
        .fill(items['edit-naiset-64-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-joista-helsinkilaisia-naiset-64-aktiiviharrastajat']) {
      await page.locator('#edit-joista-helsinkilaisia-naiset-64-aktiiviharrastajat')
        .fill(items['edit-joista-helsinkilaisia-naiset-64-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-muut-64-aktiiviharrastajat']) {
      await page.locator('#edit-muut-64-aktiiviharrastajat')
        .fill(items['edit-muut-64-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-joista-helsinkilaisia-muut-64-aktiiviharrastajat']) {
      await page.locator('#edit-joista-helsinkilaisia-muut-64-aktiiviharrastajat')
        .fill(items['edit-joista-helsinkilaisia-muut-64-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-pojat-20-aktiiviharrastajat']) {
      await page.locator('#edit-pojat-20-aktiiviharrastajat')
        .fill(items['edit-pojat-20-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-joista-helsinkilaisia-pojat-20-aktiiviharrastajat']) {
      await page.locator('#edit-joista-helsinkilaisia-pojat-20-aktiiviharrastajat')
        .fill(items['edit-joista-helsinkilaisia-pojat-20-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-tytot-20-aktiiviharrastajat']) {
      await page.locator('#edit-tytot-20-aktiiviharrastajat')
        .fill(items['edit-tytot-20-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-joista-helsinkilaisia-tytot-20-aktiiviharrastajat']) {
      await page.locator('#edit-joista-helsinkilaisia-tytot-20-aktiiviharrastajat')
        .fill(items['edit-joista-helsinkilaisia-tytot-20-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-muut-20-aktiiviharrastajat']) {
      await page.locator('#edit-muut-20-aktiiviharrastajat')
        .fill(items['edit-muut-20-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-joista-helsinkilaisia-muut-20-aktiiviharrastajat']) {
      await page.locator('#edit-joista-helsinkilaisia-muut-20-aktiiviharrastajat')
        .fill(items['edit-joista-helsinkilaisia-muut-20-aktiiviharrastajat'].value ?? '');
    }

    if (items['edit-valmentajien-ohjaajien-maara-edellisena-vuonna-yhteensa']) {
      await page.locator('#edit-valmentajien-ohjaajien-maara-edellisena-vuonna-yhteensa')
        .fill(items['edit-valmentajien-ohjaajien-maara-edellisena-vuonna-yhteensa'].value ?? '');
    }

    if (items['edit-joista-valmentaja-ja-ohjaajakoulutuksen-vok-1-5-tason-koulutukse']) {
      await page.locator('#edit-joista-valmentaja-ja-ohjaajakoulutuksen-vok-1-5-tason-koulutukse')
        .fill(items['edit-joista-valmentaja-ja-ohjaajakoulutuksen-vok-1-5-tason-koulutukse'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-sectionname']) {
      await page.locator('#edit-club-section-items-0-item-sectionname')
        .selectOption(items['edit-club-section-items-0-item-sectionname'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-sectionother']) {
      await page.locator('#edit-club-section-items-0-item-sectionother')
        .fill(items['edit-club-section-items-0-item-sectionother'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-men']) {
      await page.locator('#edit-club-section-items-0-item-men')
        .fill(items['edit-club-section-items-0-item-men'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-women']) {
      await page.locator('#edit-club-section-items-0-item-women')
        .fill(items['edit-club-section-items-0-item-women'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-adultothers']) {
      await page.locator('#edit-club-section-items-0-item-adultothers')
        .fill(items['edit-club-section-items-0-item-adultothers'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-adulthours']) {
      await page.locator('#edit-club-section-items-0-item-adulthours')
        .fill(items['edit-club-section-items-0-item-adulthours'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-seniormen']) {
      await page.locator('#edit-club-section-items-0-item-seniormen')
        .fill(items['edit-club-section-items-0-item-seniormen'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-seniorwomen']) {
      await page.locator('#edit-club-section-items-0-item-seniorwomen')
        .fill(items['edit-club-section-items-0-item-seniorwomen'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-seniorothers']) {
      await page.locator('#edit-club-section-items-0-item-seniorothers')
        .fill(items['edit-club-section-items-0-item-seniorothers'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-seniorhours']) {
      await page.locator('#edit-club-section-items-0-item-seniorhours')
        .fill(items['edit-club-section-items-0-item-seniorhours'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-boys']) {
      await page.locator('#edit-club-section-items-0-item-boys')
        .fill(items['edit-club-section-items-0-item-boys'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-girls']) {
      await page.locator('#edit-club-section-items-0-item-girls')
        .fill(items['edit-club-section-items-0-item-girls'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-juniorothers']) {
      await page.locator('#edit-club-section-items-0-item-juniorothers')
        .fill(items['edit-club-section-items-0-item-juniorothers'].value ?? '');
    }

    if (items['edit-club-section-items-0-item-juniorhours']) {
      await page.locator('#edit-club-section-items-0-item-juniorhours')
        .fill(items['edit-club-section-items-0-item-juniorhours'].value ?? '');
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

    if (items['edit-tilankayttoliite-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-tilankayttoliite-attachment-upload'].selector?.value ?? '',
        items['edit-tilankayttoliite-attachment-upload'].selector?.resultValue ?? '',
        items['edit-tilankayttoliite-attachment-upload'].value
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


test.describe('LIIKUNTATILANKAYTTO(60)', () => {
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

      logger('Delete DRAFTS', storedata, key);

    });
  }


});

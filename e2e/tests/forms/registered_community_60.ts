import {Page, test} from '@playwright/test';
import {FormData, PageHandlers, FormPage} from "../../utils/data/test_data";
import {fillHakijanTiedotRegisteredCommunity} from "../../utils/form_helpers";
import {fillFormField, fillInputField, uploadFile} from "../../utils/input_helpers";
import {generateTests} from "../../utils/test_generator_helpers";
import {Role, selectRole} from "../../utils/auth_helpers";
import {registeredCommunityApplications as applicationData} from '../../utils/data/application_data';

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
      await fillFormField(page, items['edit-acting-year'], 'edit-acting-year');
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

    if (items['edit-myonnetty-avustus']) {
      await fillFormField(page, items['edit-myonnetty-avustus'], 'edit-myonnetty-avustus')
    }

    if (items['edit-tuntimaara-yhteensa']) {
      await fillInputField(
        items['edit-tuntimaara-yhteensa'].value ?? '',
        items['edit-tuntimaara-yhteensa'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-tuntimaara-yhteensa',
        },
        page,
        'edit-tuntimaara-yhteensa'
      );
    }

    if (items['edit-vuokrat-yhteensa']) {
      await fillInputField(
        items['edit-vuokrat-yhteensa'].value ?? '',
        items['edit-vuokrat-yhteensa'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-vuokrat-yhteensa',
        },
        page,
        'edit-vuokrat-yhteensa'
      );
    }

    if (items['edit-seuraavalle-vuodelle-suunniteltu-muutos-tilojen-kaytossa-tunnit-']) {
      await page.locator('#edit-seuraavalle-vuodelle-suunniteltu-muutos-tilojen-kaytossa-tunnit-')
        .fill(items['edit-seuraavalle-vuodelle-suunniteltu-muutos-tilojen-kaytossa-tunnit-'].value ?? '');
    }

    if (items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal']) {
      await fillFormField(page, items['edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal'], 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal')
    }

  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {

    // Loop trough number input fields.
    for (const [itemKey, item] of Object.entries(items)) {
      if (item.role && item.role === 'number-input') {
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
    }

    if (items['edit-club-section']) {
      await fillFormField(page, items['edit-club-section'], 'edit-club-section')
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

test.describe('LIIKUNTATILANKAYTTO(60)', () => {
  let page: Page;

  const profileType = 'registered_community';
  const formId = '60';

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

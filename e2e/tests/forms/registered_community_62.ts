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

    if (items['edit-kenelle-haen-avustusta']) {
      await page.locator('#edit-kenelle-haen-avustusta')
        .selectOption(items['edit-kenelle-haen-avustusta'].value ?? '');
    }

    if (items['edit-acting-year']) {
      await fillFormField(page, items['edit-acting-year'], 'edit-acting-year');
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

  const profileType = 'registered_community';
  const formId = '62';

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

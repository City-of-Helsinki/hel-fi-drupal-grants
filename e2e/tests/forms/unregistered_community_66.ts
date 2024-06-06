import {Browser, Page, test} from '@playwright/test';
import {FormData, PageHandlers, FormPage} from "../../utils/data/test_data";
import {fillHakijanTiedotUnregisteredCommunity} from "../../utils/form_helpers";
import {fillFormField, fillInputField, uploadFile} from "../../utils/input_helpers";
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

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    await fillInputField(
      items['edit-yhdistyksen-kuluvan-vuoden-toiminta-avustus'].value ?? '',
      items['edit-yhdistyksen-kuluvan-vuoden-toiminta-avustus'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-yhdistyksen-kuluvan-vuoden-toiminta-avustus',
      },
      page,
      'edit-yhdistyksen-kuluvan-vuoden-toiminta-avustus'
    );

    await fillInputField(
      items['edit-selvitys-kuluvan-vuoden-toiminta-avustuksen-kaytosta'].value ?? '',
      items['edit-selvitys-kuluvan-vuoden-toiminta-avustuksen-kaytosta'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-selvitys-kuluvan-vuoden-toiminta-avustuksen-kaytosta',
      },
      page,
      'edit-selvitys-kuluvan-vuoden-toiminta-avustuksen-kaytosta'
    );

    await fillInputField(
      items['edit-yhdistyksen-kuluvan-vuoden-palkkausavustus-'].value ?? '',
      items['edit-yhdistyksen-kuluvan-vuoden-palkkausavustus-'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-yhdistyksen-kuluvan-vuoden-palkkausavustus-',
      },
      page,
      'edit-yhdistyksen-kuluvan-vuoden-palkkausavustus-'
    );

    await fillInputField(
      items['edit-selvitys-kuluvan-vuoden-palkkausavustuksen-kaytosta'].value ?? '',
      items['edit-selvitys-kuluvan-vuoden-palkkausavustuksen-kaytosta'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-selvitys-kuluvan-vuoden-palkkausavustuksen-kaytosta',
      },
      page,
      'edit-selvitys-kuluvan-vuoden-palkkausavustuksen-kaytosta'
    );

    await fillInputField(
      items['edit-sanallinen-selvitys-avustuksen-kaytosta'].value ?? '',
      items['edit-sanallinen-selvitys-avustuksen-kaytosta'].selector ?? {
        type: 'data-drupal-selector-sequential',
        name: 'data-drupal-selector',
        value: 'edit-sanallinen-selvitys-avustuksen-kaytosta',
      },
      page,
      'edit-sanallinen-selvitys-avustuksen-kaytosta'
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

test.describe('NUORTOIMPALKENNAKKO(66)', () => {
  let page: Page;
  let browser: Browser;

  const profileType = 'unregistered_community';
  const formId = '66';

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

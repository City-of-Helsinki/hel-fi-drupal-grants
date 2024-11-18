import {Page, test} from '@playwright/test';
import {FormData, PageHandlers, FormPage} from "../../utils/data/test_data";
import {fillHakijanTiedotRegisteredCommunity} from "../../utils/form_helpers";
import {fillFormField} from "../../utils/input_helpers";
import {generateTests} from "../../utils/test_generator_helpers";
import {Role, selectRole} from "../../utils/auth_helpers";
import {registeredCommunityApplications as applicationData} from '../../utils/data/application_data';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotRegisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-acting-year']) {
      await fillFormField(page, items['edit-acting-year'], 'edit-acting-year');
    }

    // Kulttuurin erityisavustus 1
    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    // Liikunnan erityisavustus 1.
    if (items['edit-subventions-items-1-amount']) {
      // Using the items['edit-subventions-items-0-amount'].value doesn't seem
      // to work as expected. Use
      await page.locator('#edit-subventions-items-1-amount')
        .fill(items['edit-subventions-items-1-amount'].value ?? '');
    }

    if (items['edit-compensation-purpose']) {
      await page.getByRole('textbox', {name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista'})
        .fill(items['edit-compensation-purpose'].value ?? '');
    }

    if (items['edit-myonnetty-avustus']) {
      await fillFormField(page, items['edit-myonnetty-avustus'], 'edit-myonnetty-avustus')
    }

    if (items['edit-haettu-avustus-tieto']) {
      await fillFormField(page, items['edit-haettu-avustus-tieto'], 'edit-haettu-avustus-tieto')
    }
  },
  '3_tarkemmat_tiedot': async (page: Page, {items}: FormPage) => {

    const skipItems = [
      'edit-hankkeen-toimenpiteet-alkupvm',
      'edit-hankkeen-toimenpiteet-loppupvm',
      'edit-ensisijainen-taiteen-ala',
    ];

    // Loop all items from page 3.
    for (const [itemKey, item] of Object.entries(items)) {
      // Skip the date fields and the select list, and handle them separately.
      if (skipItems.includes(itemKey)) {
        continue;
      }
      await fillFormField(page, item, itemKey);
    }

    // Handle the "taiteenala" select list separately.
    if (items['edit-ensisijainen-taiteen-ala']) {
      await page.selectOption('select#edit-ensisijainen-taiteen-ala',
        items['edit-ensisijainen-taiteen-ala'].value ?? '');
    }

    // Handle date fields separately.
    if (items['edit-hankkeen-toimenpiteet-alkupvm']) {
      await page.getByLabel('Alkupäivämäärä')
        .fill(items['edit-hankkeen-toimenpiteet-alkupvm'].value ?? '');
    }

    if (items['edit-hankkeen-toimenpiteet-loppupvm']) {
      await page.getByLabel('Loppupäivämäärä')
        .fill(items['edit-hankkeen-toimenpiteet-loppupvm'].value ?? '');
    }
  },
  '4_talousarvio': async (page: Page, {items}: FormPage) => {

    // Loop all items from page 4.
    for (const [itemKey, item] of Object.entries(items)) {
      await fillFormField(page, item, itemKey);
    }
  },
  'lisatiedot_ja_liitteet': async (page: Page, {items}: FormPage) => {

    // Loop all items from page 5.
    for (const [itemKey, item] of Object.entries(items)) {
      await fillFormField(page, item, itemKey);
    }
  },
  'webform_preview': async (page: Page, {items}: FormPage) => {
    if (items['accept_terms_1']) {
      // Check data on confirmation page
      await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
    }
  },
};

test.describe('KUVAERILLIS(70)', () => {
  let page: Page;

  const profileType = 'registered_community';
  const formId = '70';

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

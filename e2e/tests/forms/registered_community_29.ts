import {Page, expect, test} from '@playwright/test';
import {
  FormData, FormPage,
  PageHandlers,
} from '../../utils/data/test_data';
import {fakerFI as faker} from '@faker-js/faker'
import {
  fillGrantsFormPage,
  fillHakijanTiedotRegisteredCommunity,
  fillInputField,
  hideSlidePopup,
} from '../../utils/form_helpers';

import {
  registeredCommunityApplications as applicationData
} from '../../utils/data/application_data';
import {selectRole} from '../../utils/auth_helpers';
import {getObjectFromEnv, slowLocator} from '../../utils/helpers';
import {validateSubmission} from '../../utils/validation_helpers';

const profileType = 'registered_community';
const formId = '29';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    // First page is always same, so use function to fill this.
    await fillHakijanTiedotRegisteredCommunity(items, page);

  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    // We need to check the presence of every item so that removed items will
    // not be filled. This is to enable testing for missing values & error handling.
    if (items['edit-acting-year']) {
      // await fillSelectField(items['edit-acting-year'].selector, page, '');
      await page.locator('#edit-acting-year').selectOption('2024');
    }

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    await page.getByRole('textbox', {name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista'}).fill('lyhyt kuvasu');
    await page.getByLabel('Kuvaus lainoista ja takauksista').fill('asdadsdadaas');
    await page.getByLabel('Kuvaus tiloihin liittyvästä tuesta').fill('sdfdfsfdsdsf');


  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {

    await page.getByRole('textbox', {name: 'Toiminnan kuvaus'}).fill('asffsafsasfa');
    await page.getByText('Ei', {exact: true}).click();
    await page.locator('#edit-fee-person').fill('64');
    await page.locator('#edit-fee-community').fill('64');
    await page.getByRole('textbox', {name: 'Henkilöjäseniä yhteensä Henkilöjäseniä yhteensä'}).fill('123');
    await page.getByRole('textbox', {name: 'Helsinkiläisiä henkilöjäseniä yhteensä'}).fill('22');
    await page.getByRole('textbox', {name: 'Yhteisöjäseniä Yhteisöjäseniä'}).fill('44');
    await page.getByRole('textbox', {name: 'Helsinkiläisiä yhteisöjäseniä yhteensä'}).fill('55');

  },
  'lisatiedot_ja_liitteet': async (page: Page, {items}: FormPage) => {

    if (items['edit-additional-information']) {
      await fillInputField(
        faker.lorem.sentences(3),
        {
          type: 'role',
          name: 'Role',
          details: {
            role: 'textbox',
            options: {
              name: 'Lisätiedot'
            }
          },
        },
        page,
        'edit-additional-information');
    }


    await page.getByRole('group', {name: 'Yhteisön säännöt'}).getByLabel('Liite toimitetaan myöhemmin').check();
    await page.getByRole('group', {name: 'Vahvistettu tilinpäätös'}).getByLabel('Liite toimitetaan myöhemmin').check();

    await page.getByRole('group', {name: 'Vahvistettu toimintakertomus'}).getByLabel('Liite toimitetaan myöhemmin').check();
    await page.getByRole('group', {name: 'Vahvistettu tilin- tai toiminnantarkastuskertomus'}).getByLabel('Liite toimitetaan myöhemmin').check();
    await page.locator('#edit-vuosikokouksen-poytakirja--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    await page.locator('#edit-toimintasuunnitelma--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    await page.locator('#edit-talousarvio--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    await page.getByLabel('Lisäselvitys liitteistä').fill('sdfdfsdfsdfsdfsdfsdfs');

  },
  'webform_preview': async (page: Page, {items}: FormPage) => {
    if (items['accept_terms_1']) {
      // Check data on confirmation page
      await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
    }
  },
};


test.describe('ECONOMICGRANTAPPLICATION(29)', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    page.locator = slowLocator(page, 10000);

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

import {Page, expect, test} from '@playwright/test';
import {
  FormData,
  FormPage,
  PageHandlers,
  Selector,
} from "../../utils/data/test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {
  fillGrantsFormPage, fillInputField,
  fillSelectField,
  hideSlidePopup,
  fillCheckboxField,
  fillHakijanTiedotRegisteredCommunity
} from "../../utils/form_helpers";

import {
  registeredCommunityApplications as applicationData
} from "../../utils/data/application_data";
import {selectRole} from "../../utils/auth_helpers";
import {getObjectFromEnv, slowLocator} from "../../utils/helpers";
import {validateSubmission} from "../../utils/validation_helpers";

const profileType = 'registered_community';
const formId = '51';

const formPages: PageHandlers = {
  "1_hakijan_tiedot": async (page: Page, formPageObject) => {
    await page.getByRole('textbox', {name: 'Sähköpostiosoite'}).fill('asadsdqwetest@example.org');
    await page.getByLabel('Yhteyshenkilö').fill('asddsa');
    await page.getByLabel('Puhelinnumero').fill('0234432243');
    await page.locator('#edit-community-address-community-address-select').selectOption({index: 1});

    await page.locator('#edit-bank-account-account-number-select').selectOption({index: 1});
  },
  "2_avustustiedot": async (page: Page, formPageObject: FormPage) => {

    // // @ts-ignore
    // if (formPageObject.items.acting_year.selector) {
    //   // @ts-ignore
    //   await fillSelectField(formPageObject.items.acting_year.selector, page, '');
    // }
    // // @ts-ignore
    // if (formPageObject.items.subvention_amount.value) {
    //   // @ts-ignore
    //   await page.locator('#edit-subventions-items-0-amount').fill(formPageObject.items.subvention_amount.value);
    // }

    await page.locator('#edit-acting-year').selectOption({index: 1});
    await page.locator('#edit-subventions-items-0-amount').fill('128,00€');
    await page.getByRole('textbox', {name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista'}).fill('lyhyt kuvasu');
    await page.getByLabel('Kuvaus lainoista ja takauksista').fill('asdadsdadaas');
    await page.getByLabel('Kuvaus tiloihin liittyvästä tuesta').fill('sdfdfsfdsdsf');

    await page.pause();

  },
  "3_yhteison_tiedot": async (page: Page, formPageObject: FormPage) => {

    await page.getByRole('textbox', {name: 'Toiminnan kuvaus'}).fill('asffsafsasfa');
    await page.getByText('Ei', {exact: true}).click();
    await page.locator('#edit-fee-person').fill('64');
    await page.locator('#edit-fee-community').fill('64');
    await page.getByRole('textbox', {name: 'Henkilöjäseniä yhteensä Henkilöjäseniä yhteensä'}).fill('123');
    await page.getByRole('textbox', {name: 'Helsinkiläisiä henkilöjäseniä yhteensä'}).fill('22');
    await page.getByRole('textbox', {name: 'Yhteisöjäseniä Yhteisöjäseniä'}).fill('44');
    await page.getByRole('textbox', {name: 'Helsinkiläisiä yhteisöjäseniä yhteensä'}).fill('55');

    await page.pause();

  },
  "lisatiedot_ja_liitteet": async (page: Page, formPageObject: FormPage) => {

    // @ts-ignore
    if (formPageObject.items['edit-additional-information']) {
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

    // await page.getByRole('textbox', {name: 'Lisätiedot'}).fill('qwfqwfqwfwfqfwq');


    await page.getByRole('group', {name: 'Yhteisön säännöt'}).getByLabel('Liite toimitetaan myöhemmin').check();


    await page.getByRole('group', {name: 'Vahvistettu tilinpäätös'}).getByLabel('Liite toimitetaan myöhemmin').check();

    await page.getByRole('group', {name: 'Vahvistettu toimintakertomus'}).getByLabel('Liite toimitetaan myöhemmin').check();
    await page.getByRole('group', {name: 'Vahvistettu tilin- tai toiminnantarkastuskertomus'}).getByLabel('Liite toimitetaan myöhemmin').check();
    await page.locator('#edit-vuosikokouksen-poytakirja--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    await page.locator('#edit-toimintasuunnitelma--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    await page.locator('#edit-talousarvio--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    await page.getByLabel('Lisäselvitys liitteistä').fill('sdfdfsdfsdfsdfsdfsdfs');

    await page.pause();

  },
  "webform_preview": async (page: Page, formPageObject: Object) => {
    await page.getByText('Tarkista lähetyksesi. Lähetyksesi on valmis vasta, kun painat "Lähetä"-painikett').click();
    await expect(page.getByText('Helsingin kaupungin myöntämiin avustuksiin sovelletaan seuraavia avustusehtoja.')).toBeVisible();
    await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();

    await page.pause();

  },
};


test.describe('KASKOYLEIS(51)', () => {
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

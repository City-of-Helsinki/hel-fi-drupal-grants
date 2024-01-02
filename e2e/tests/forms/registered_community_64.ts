import {Page, expect, test} from '@playwright/test';
import {
  FormData,
  PageHandlers,
} from "../../utils/data/test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {
  fillGrantsFormPage, fillInputField, fillSelectField,
  hideSlidePopup,
} from "../../utils/form_helpers";

import {
  registeredCommunityApplications as applicationData
} from "../../utils/data/application_data";
import {selectRole} from "../../utils/auth_helpers";
import {getObjectFromEnv, slowLocator} from "../../utils/helpers";
import {validateSubmission} from "../../utils/validation_helpers";

const profileType = 'registered_community';
const formId = '64';

const formPages: PageHandlers = {
  "1_hakijan_tiedot": async (page: Page, formPageObject: Object) => {

    if (formPageObject.items['edit-email']) {
      await page.getByRole('textbox', {name: 'Sähköpostiosoite'}).fill(formPageObject.items['edit-email'].value);
    }
    if (formPageObject.items['edit-contact-person']) {
      await page.getByLabel('Yhteyshenkilö').fill(formPageObject.items['edit-contact-person'].value);
    }
    if (formPageObject.items['edit-contact-person-phone-number']) {
      await page.getByLabel('Puhelinnumero').fill(formPageObject.items['edit-contact-person-phone-number'].value);
    }

    if (formPageObject.items['edit-community-address-community-address-select']) {
      await fillSelectField(formPageObject.items['edit-community-address-community-address-select'].selector, page, undefined);
    }
    if (formPageObject.items['bank-account']) {
      await fillSelectField(
        {
          type: 'dom-id-first',
          name: 'community-officials-selector',
          value: '#edit-bank-account-account-number-select'
        },
        page, undefined);
    }
    if (formPageObject.items['edit-community-officials-items-0-item-community-officials-select']) {
      await fillSelectField(
        {
          type: 'dom-id-first',
          name: 'community-officials-selector',
          value: '#edit-community-officials-items-0-item-community-officials-select'
        },
        page, undefined);
    }

  },
  "2_avustustiedot": async (page: Page, formPageObject: Object) => {

    console.log(formPageObject.items);

    // @ts-ignore
    if (formPageObject.items.acting_year.selector) {
      // @ts-ignore
      await fillSelectField(formPageObject.items.acting_year.selector, page, '');
    }
    // @ts-ignore
    if (formPageObject.items.subvention_amount.value) {
      // @ts-ignore
      await page.locator('#edit-subventions-items-0-amount').fill(formPageObject.items.subvention_amount.value);
    }

    await page.getByRole('textbox',
      {name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista *'})
      .fill(faker.lorem.sentences(3));

    await page.getByLabel('Kuvaus lainoista ja takauksista').fill('Abc123');
    await page.getByLabel('Kuvaus tiloihin liittyvästä tuesta').fill('Dasdasdasd');
  },
  "3_yhteison_tiedot": async (page: Page, formPageObject: Object) => {
    await page.getByLabel('Henkilöjäsenen jäsenmaksu (€ / vuosi)').fill('10,12€');
    await page.getByLabel('Yhteisöjäsen (€ / vuosi)').fill('12,12€');
    await page.getByRole('textbox', {name: 'Henkilöjäseniä yhteensä Henkilöjäseniä yhteensä'}).fill('123');
    await page.getByRole('textbox', {name: 'Helsinkiläisiä henkilöjäseniä yhteensä'}).fill('22');
    await page.getByRole('textbox', {name: 'Yhteisöjäseniä Yhteisöjäseniä'}).fill('44');
    await page.getByRole('textbox', {name: 'Helsinkiläisiä yhteisöjäseniä yhteensä'}).fill('55');
  },
  "lisatiedot_ja_liitteet": async (page: Page, formPageObject: Object) => {
    await page.getByRole('textbox', {name: 'Lisätiedot'}).fill('liiteselvitys');
  },
  "webform_preview": async (page: Page, formPageObject: Object) => {
    // Check data on confirmation page
    await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
    await page.pause();

  },
};

test.describe('ASUKASPIEN(64)', () => {
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
      await validateSubmission(
        key,
        page,
        obj,
        storedata
      );
    });
  }
});

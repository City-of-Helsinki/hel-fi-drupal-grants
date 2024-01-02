import {Page, expect, test} from '@playwright/test';
import {
  FormData,
  profileDataPrivatePerson,
  PageHandlers, FormPage
} from "../../utils/data/test_data";
import {fillGrantsFormPage, fillInputField, fillHakijanTiedotPrivatePerson} from "../../utils/form_helpers";

import {
  privatePersonApplications as applicationData
} from '../../utils/data/application_data';
import {selectRole} from "../../utils/auth_helpers";
import {
  slowLocator,
  getObjectFromEnv,
  clickContinueButton
} from "../../utils/helpers";
import {hideSlidePopup, fillSelectField} from '../../utils/form_helpers'
import {validateSubmission} from "../../utils/validation_helpers";

const profileType = 'private_person';
const formId = '48';


// @ts-ignore
const formPages: PageHandlers = {
  "1_hakijan_tiedot": async (page: Page, formPageObject: FormPage) => {
    await fillHakijanTiedotPrivatePerson(formPageObject.items, page);
  },
  "2_avustustiedot": async (page: Page, formPageObject: FormPage) => {

    // @ts-ignore
    if (formPageObject.items.acting_year.selector) {
      // @ts-ignore
      await fillSelectField(formPageObject.items.acting_year.selector, page, '');
    }

    if (formPageObject.items.subvention_amount.value) {
      await page.locator('#edit-subventions-items-0-amount').fill(formPageObject.items.subvention_amount.value);
    }

    await page.locator('#edit-ensisijainen-taiteen-ala').selectOption('Museo');
    await page.getByRole('textbox', {name: 'Hankkeen nimi'}).fill('qweqweqew');
    await page.locator('#edit-kyseessa-on-festivaali-tai-tapahtuma').getByText('Ei').click();
    await page.getByRole('textbox', {name: 'Hankkeen tai toiminnan lyhyt esittelyteksti'}).fill('afdfdsd dsg sgd gsd');

  },
  "3_yhteison_tiedot": async (page: Page, formPageObject: FormPage) => {

    await page.getByLabel('Henkilöjäseniä yhteensä', {exact: true}).fill('12');
    await page.getByLabel('Helsinkiläisiä henkilöjäseniä yhteensä').fill('12');
    await page.getByLabel('Yhteisöjäseniä', {exact: true}).fill('23');
    await page.getByLabel('Helsinkiläisiä yhteisöjäseniä yhteensä').fill('34');
    await page.getByLabel('Kokoaikaisia: Henkilöitä').fill('23');
    await page.getByLabel('Kokoaikaisia: Henkilötyövuosia').fill('34');
    await page.getByLabel('Osa-aikaisia: Henkilöitä').fill('23');
    await page.getByLabel('Osa-aikaisia: Henkilötyövuosia').fill('23');
    await page.getByLabel('Vapaaehtoisia: Henkilöitä').fill('12');

  },
  "4_suunniteltu_toiminta": async (page: Page, formPageObject: FormPage) => {

    await page.getByLabel('Tapahtuma- tai esityspäivien määrä Helsingissä').fill('12');
    await page.getByRole('group', {name: 'Määrä Helsingissä'}).getByLabel('Esitykset').fill('2');
    await page.getByRole('group', {name: 'Määrä Helsingissä'}).getByLabel('Näyttelyt').fill('3');
    await page.getByRole('group', {name: 'Määrä Helsingissä'}).getByLabel('Työpaja tai muu osallistava toimintamuoto').fill('4');
    await page.getByRole('group', {name: 'Määrä kaikkiaan'}).getByLabel('Esitykset').fill('3');
    await page.getByRole('group', {name: 'Määrä kaikkiaan'}).getByLabel('Näyttelyt').fill('4');
    await page.getByRole('group', {name: 'Määrä kaikkiaan'}).getByLabel('Työpaja tai muu osallistava toimintamuoto').fill('5');
    await page.getByRole('textbox', {name: 'Kävijämäärä Helsingissä'}).fill('12222');
    await page.getByRole('textbox', {name: 'Kävijämäärä kaikkiaan'}).fill('343444');
    await page.getByRole('textbox', {name: 'Kantaesitysten määrä'}).fill('12');
    await page.getByRole('textbox', {name: 'Ensi-iltojen määrä Helsingissä'}).fill('23');
    await page.getByLabel('Tilan nimi').fill('sdggdsgds');
    await page.getByLabel('Postinumero').fill('00100');
    await page.getByText('Ei', {exact: true}).click();
    await page.getByLabel('Ensimmäisen yleisölle avoimen tilaisuuden päivämäärä').fill('2024-12-12');
    await page.getByLabel('Hanke alkaa').fill('2030-01-01');
    await page.getByLabel('Hanke loppuu').fill('2030-02-02');
    await page.getByRole('textbox', {name: 'Laajempi hankekuvaus Laajempi hankekuvaus'}).fill('sdgdsgdgsgds');

  },
  "5_toiminnan_lahtokohdat": async (page: Page, formPageObject: FormPage) => {

    await page.getByLabel('Keitä toiminnalla tavoitellaan? Miten kyseiset kohderyhmät aiotaan tavoittaa ja mitä osaamista näiden kanssa työskentelyyn on?').fill('sdgsgdsdg');
    await page.getByRole('textbox', {name: 'Nimeä keskeisimmät yhteistyökumppanit ja kuvaa yhteistyön muotoja ja ehtoja'}).fill('werwerewr');


  },
  "6_talous": async (page: Page, formPageObject: FormPage) => {

    await page.getByText('Ei', {exact: true}).click();
    await page.getByRole('textbox', {name: 'Muut avustukset (€)'}).fill('234');
    await page.getByLabel('Muut oman toiminnan tulot (€)').fill('123');
    await page.getByLabel('Palkat ja palkkiot esiintyjille ja taiteilijoille (€)').fill('123');
    await page.getByLabel('Muut palkat ja palkkiot (tuotanto, tekniikka jne) (€)').fill('123');
    await page.getByRole('textbox', {name: 'Esityskorvaukset (€) '}).fill('123');
    await page.getByLabel('Matkakulut (€)').fill('123');
    await page.getByLabel('Kuljetus (sis. autovuokrat) (€)').fill('123');
    await page.getByLabel('Tiedotus, markkinointi ja painatus (€)').fill('123');
    await page.getByLabel('Kuvaus menosta').fill('11wdgwgregre');

    if (formPageObject.items['edit-budget-static-income-entryfees']) {
      await fillInputField(
        formPageObject.items['edit-budget-static-income-entryfees'].value ?? '',
        formPageObject.items['edit-budget-static-income-entryfees'].selector,
        page, 'edit-budget-static-income-entryfees');
    }


    if (formPageObject.items['edit-budget-other-cost-items-0-item-value']) {
      await fillInputField(
        formPageObject.items['edit-budget-other-cost-items-0-item-value'].value ?? '',
        formPageObject.items['edit-budget-other-cost-items-0-item-value'].selector,
        page,
        'edit-budget-other-cost-items-0-item-value');
    }

    // await page.getByLabel('Yksityinen rahoitus (esim. sponsorointi, yritysyhteistyö,lahjoitukset) (€)').fill('234');
    // await page.getByLabel('Pääsy- ja osallistumismaksut (€)').fill('123');
    // await page.getByLabel('Yhteisön oma rahoitus (€)').fill('123');
    // await page.getByLabel('Henkilöstösivukulut palkoista ja palkkioista (n. 30%) (€)').fill('123');
    // await page.getByLabel('Tekniikka, laitevuokrat ja sähkö (€)').fill('123');
    // await page.getByLabel('Kiinteistöjen käyttökulut ja vuokrat (€)').fill('123');
    // await page.getByLabel('Määrä (€)').fill('234');
    // await page.getByLabel('Sisältyykö toiminnan toteuttamiseen jotain muuta rahanarvoista panosta tai vaihtokauppaa, joka ei käy ilmi budjetista?').fill('erggergergegerger');
  },
  "lisatiedot_ja_liitteet": async (page: Page, formPageObject: FormPage) => {

    await page.getByRole('textbox', {name: 'Lisätiedot'}).fill('fewqfwqfwqfqw');
    await page.getByLabel('Lisäselvitys liitteistä').fill('sdfdsfdsfdfs');

  },
  "webform_preview": async (page: Page, formPageObject: FormPage) => {
    // Check data on confirmation page
    await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
  },
};


test.describe('Private person KUVAPROJ(48)', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

    page.locator = slowLocator(page, 10000);

    await selectRole(page, 'PRIVATE_PERSON');
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

          console.log('Validate dubmissions', storedata);

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

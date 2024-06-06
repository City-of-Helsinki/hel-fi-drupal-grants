import {Page, test} from '@playwright/test';
import {FormData, PageHandlers, FormPage} from "../../utils/data/test_data";
import {fillHakijanTiedotPrivatePerson} from "../../utils/form_helpers";
import {fillFormField, fillInputField} from "../../utils/input_helpers";
import {generateTests} from "../../utils/test_generator_helpers";
import {Role, selectRole} from "../../utils/auth_helpers";
import {privatePersonApplications as applicationData} from '../../utils/data/application_data';

/**
 * Create object containing handler functions.
 */
const formPages: PageHandlers = {

  /**
   * Each of the items in this object represents a handler function for given
   * page that fills form fields with faker data.
   *
   * @param page
   *  Playwright page object
   *
   * @param formPageObject
   *  Form page containing all the items for given form page.
   *
   */
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    // First page is always same, so use function to fill this.
    await fillHakijanTiedotPrivatePerson(items, page);

  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    // We need to check the presence of every item so that removed items will
    // not be filled. This is to enable testing for missing values & error handling.
    if (items['edit-acting-year']) {
      await fillFormField(page, items['edit-acting-year'], 'edit-acting-year');
    }

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    if (items['edit-ensisijainen-taiteen-ala']) {
      await page.locator('#edit-ensisijainen-taiteen-ala').selectOption(items['edit-ensisijainen-taiteen-ala'].value ?? '');
    }

    if (items['edit-hankkeen-nimi']) {
      await page.getByRole('textbox', {name: 'Hankkeen nimi'})
        .fill(items['edit-hankkeen-nimi'].value ?? '');
    }

    if (items['edit-kyseessa-on-festivaali-tai-tapahtuma-0']) {
      await page.locator('#edit-kyseessa-on-festivaali-tai-tapahtuma')
        .getByText('Ei').click();
    }
    if (items['edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti']) {
      await page.getByRole('textbox', {name: 'Hankkeen tai toiminnan lyhyt esittelyteksti'})
        .fill(items['edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti'].value ?? '');
    }

    if (items['edit-myonnetty-avustus']) {
      await fillFormField(page, items['edit-myonnetty-avustus'], 'edit-myonnetty-avustus')
    }

  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {
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

  /**
   * You can use playwright provided selectors directly, no need for fancy loops
   * or anything. With more complex fields this will get tedious though.
   *
   * @param page
   * @param formPageObject
   */
  '4_suunniteltu_toiminta': async (page: Page, {items}: FormPage) => {

    if (items['edit-tapahtuma-tai-esityspaivien-maara-helsingissa']) {
      await fillInputField(
        items['edit-tapahtuma-tai-esityspaivien-maara-helsingissa'].value ?? '',
        items['edit-tapahtuma-tai-esityspaivien-maara-helsingissa'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-tapahtuma-tai-esityspaivien-maara-helsingissa',
        },
        page,
        'edit-tapahtuma-tai-esityspaivien-maara-helsingissa'
      );
    }

    if (items['edit-esitykset-maara-helsingissa']) {
      await fillInputField(
        items['edit-esitykset-maara-helsingissa'].value ?? '',
        items['edit-esitykset-maara-helsingissa'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-esitykset-maara-helsingissa',
        },
        page,
        'edit-esitykset-maara-helsingissa'
      );
    }

    if (items['edit-nayttelyt-maara-helsingissa']) {
      await fillInputField(
        items['edit-nayttelyt-maara-helsingissa'].value ?? '',
        items['edit-nayttelyt-maara-helsingissa'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-nayttelyt-maara-helsingissa',
        },
        page,
        'edit-nayttelyt-maara-helsingissa'
      );
    }

    if (items['edit-tyopaja-maara-helsingissa']) {
      await fillInputField(
        items['edit-tyopaja-maara-helsingissa'].value ?? '',
        items['edit-tyopaja-maara-helsingissa'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-tyopaja-maara-helsingissa',
        },
        page,
        'edit-tyopaja-maara-helsingissa'
      );
    }

    if (items['edit-esitykset-maara-kaikkiaan']) {
      await fillInputField(
        items['edit-esitykset-maara-kaikkiaan'].value ?? '',
        items['edit-esitykset-maara-kaikkiaan'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-esitykset-maara-kaikkiaan',
        },
        page,
        'edit-esitykset-maara-kaikkiaan'
      );
    }

    if (items['edit-nayttelyt-maara-kaikkiaan']) {
      await fillInputField(
        items['edit-nayttelyt-maara-kaikkiaan'].value ?? '',
        items['edit-nayttelyt-maara-kaikkiaan'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-nayttelyt-maara-kaikkiaan',
        },
        page,
        'edit-nayttelyt-maara-kaikkiaan'
      );
    }

    if (items['edit-tyopaja-maara-kaikkiaan']) {
      await fillInputField(
        items['edit-tyopaja-maara-kaikkiaan'].value ?? '',
        items['edit-tyopaja-maara-kaikkiaan'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-tyopaja-maara-kaikkiaan',
        },
        page,
        'edit-tyopaja-maara-kaikkiaan'
      );
    }

    if (items['edit-maara-helsingissa']) {
      await fillInputField(
        items['edit-maara-helsingissa'].value ?? '',
        items['edit-maara-helsingissa'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-maara-helsingissa',
        },
        page,
        'edit-maara-helsingissa'
      );
    }

    if (items['edit-maara-kaikkiaan']) {
      await fillInputField(
        items['edit-maara-kaikkiaan'].value ?? '',
        items['edit-maara-kaikkiaan'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-maara-kaikkiaan',
        },
        page,
        'edit-maara-kaikkiaan'
      );
    }

    if (items['edit-kantaesitysten-maara']) {
      await fillInputField(
        items['edit-kantaesitysten-maara'].value ?? '',
        items['edit-kantaesitysten-maara'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-kantaesitysten-maara',
        },
        page,
        'edit-kantaesitysten-maara'
      );
    }

    if (items['edit-ensi-iltojen-maara-helsingissa']) {
      await fillInputField(
        items['edit-ensi-iltojen-maara-helsingissa'].value ?? '',
        items['edit-ensi-iltojen-maara-helsingissa'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-ensi-iltojen-maara-helsingissa',
        },
        page,
        'edit-ensi-iltojen-maara-helsingissa'
      );
    }

    if (items['edit-ensimmainen-yleisolle-avoimen-tilaisuuden-paikka-helsingissa']) {
      await page.getByLabel('Tilan nimi')
        .fill(items['edit-ensimmainen-yleisolle-avoimen-tilaisuuden-paikka-helsingissa'].value ?? '');
    }

    if (items['edit-postinumero']) {
      await fillInputField(
        items['edit-postinumero'].value ?? '',
        items['edit-postinumero'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-postinumero',
        },
        page,
        'edit-postinumero'
      );
    }

    if (items['edit-kyseessa-on-kaupungin-omistama-tila-1']) {
      await page.getByText('Ei', {exact: true})
        .click();
    }

    if (items['edit-tila']) {
      await fillFormField(page, items['edit-tila'], 'edit-tila')
    }

    if (items['edit-ensimmaisen-yleisolle-avoimen-tilaisuuden-paivamaara']) {
      await page.getByLabel('Ensimmäisen yleisölle avoimen tilaisuuden päivämäärä')
        .fill(items['edit-ensimmaisen-yleisolle-avoimen-tilaisuuden-paivamaara'].value ?? '');
    }

    if (items['edit-festivaalin-tai-tapahtuman-kohdalla-tapahtuman-paivamaarat']) {
      await page.getByLabel('Festivaalin tai tapahtuman kohdalla tapahtuman päivämäärät')
        .fill(items['edit-festivaalin-tai-tapahtuman-kohdalla-tapahtuman-paivamaarat'].value ?? '');
    }

    if (items['edit-hanke-alkaa']) {
      await page.getByLabel('Hanke alkaa')
        .fill(items['edit-hanke-alkaa'].value ?? '');
    }

    if (items['edit-hanke-loppuu']) {
      await page.getByLabel('Hanke loppuu')
        .fill(items['edit-hanke-loppuu'].value ?? '');
    }

    if (items['edit-laajempi-hankekuvaus']) {
      await page.getByRole('textbox', {name: 'Laajempi hankekuvaus Laajempi hankekuvaus'})
        .fill(items['edit-laajempi-hankekuvaus'].value ?? '');
    }

  },
  /**
   * Fill similar fields with loop. Needs to have all selectors defined, either
   * here or in the data definition.
   *
   * @param page
   * @param formPageObject
   */
  '5_toiminnan_lahtokohdat': async (page: Page, {items}: FormPage) => {

    // Loop items, all have selectors defined, so we can use looping.
    for (const [itemKey, item]
      of Object.entries(items)) {
      await fillInputField(
        item.value ?? '',
        item.selector ?? {
          type: 'data-drupal-selector',
          name: 'data-drupal-selector',
          value: itemKey,
        },
        page,
        itemKey
      );
    }

  },
  '6_talous': async (page: Page, {items}: FormPage) => {

    let thisItem;

    if (items['edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-1']) {
      await page.getByText('Kyllä', {exact: true}).click();
    }

    if (items['edit-budget-static-income-plannedothercompensations']) {
      await fillInputField(
        items['edit-budget-static-income-plannedothercompensations'].value ?? '',
        items['edit-budget-static-income-plannedothercompensations'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-income-plannedothercompensations',
        },
        page,
        'edit-budget-static-income-plannedothercompensations'
      );
    }

    if (items['edit-budget-static-income-sponsorships']) {
      await fillInputField(
        items['edit-budget-static-income-sponsorships'].value ?? '',
        items['edit-budget-static-income-sponsorships'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-income-sponsorships',
        },
        page,
        'edit-budget-static-income-sponsorships'
      );
    }

    if (items['edit-budget-static-income-entryfees']) {
      await fillInputField(
        items['edit-budget-static-income-entryfees'].value ?? '',
        items['edit-budget-static-income-entryfees'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-income-entryfees',
        },
        page,
        'edit-budget-static-income-entryfees'
      );
    }

    if (items['edit-budget-static-income-sales']) {
      await fillInputField(
        items['edit-budget-static-income-sales'].value ?? '',
        items['edit-budget-static-income-sales'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-income-sales',
        },
        page,
        'edit-budget-static-income-entryfees'
      );
    }

    if (items['edit-budget-static-income-ownfunding']) {
      await fillInputField(
        items['edit-budget-static-income-ownfunding'].value ?? '',
        items['edit-budget-static-income-ownfunding'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-income-ownfunding',
        },
        page,
        'edit-budget-static-income-ownfunding'
      );
    }

    if (items['edit-budget-static-cost-personnelsidecosts']) {
      await fillInputField(
        items['edit-budget-static-cost-personnelsidecosts'].value ?? '',
        items['edit-budget-static-cost-personnelsidecosts'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-cost-personnelsidecosts',
        },
        page,
        'edit-budget-static-cost-personnelsidecosts'
      );
    }

    if (items['edit-budget-static-cost-performerfees']) {
      await fillInputField(
        items['edit-budget-static-cost-performerfees'].value ?? '',
        items['edit-budget-static-cost-performerfees'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-cost-performerfees',
        },
        page,
        'edit-budget-static-cost-performerfees'
      );
    }

    if (items['edit-budget-static-cost-otherfees']) {
      await fillInputField(
        items['edit-budget-static-cost-otherfees'].value ?? '',
        items['edit-budget-static-cost-otherfees'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-cost-otherfees',
        },
        page,
        'edit-budget-static-cost-otherfees'
      );
    }

    if (items['edit-budget-static-cost-showcosts']) {
      await fillInputField(
        items['edit-budget-static-cost-showcosts'].value ?? '',
        items['edit-budget-static-cost-showcosts'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-cost-showcosts',
        },
        page,
        'edit-budget-static-cost-showcosts'
      );
    }

    if (items['edit-budget-static-cost-travelcosts']) {
      await fillInputField(
        items['edit-budget-static-cost-travelcosts'].value ?? '',
        items['edit-budget-static-cost-travelcosts'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-cost-travelcosts',
        },
        page,
        'edit-budget-static-cost-travelcosts'
      );
    }

    if (items['edit-budget-static-cost-transportcosts']) {
      thisItem = items['edit-budget-static-cost-transportcosts'];
      await fillInputField(
        thisItem.value ?? '',
        thisItem.selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-cost-transportcosts',
        },
        page,
        'edit-budget-static-cost-transportcosts'
      );
    }

    if (items['edit-budget-static-cost-equipment']) {
      thisItem = items['edit-budget-static-cost-equipment'];
      await fillInputField(
        thisItem.value ?? '',
        thisItem.selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-cost-equipment',
        },
        page,
        'edit-budget-static-cost-equipment'
      );
    }

    if (items['edit-budget-static-cost-premises']) {
      thisItem = items['edit-budget-static-cost-premises'];
      await fillInputField(
        thisItem.value ?? '',
        thisItem.selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-cost-premises',
        },
        page,
        'edit-budget-static-cost-premises'
      );
    }

    if (items['edit-budget-static-cost-marketing']) {
      thisItem = items['edit-budget-static-cost-marketing'];
      await fillInputField(
        thisItem.value ?? '',
        thisItem.selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-budget-static-cost-marketing',
        },
        page,
        'edit-budget-static-cost-marketing'
      );
    }

    if (items['edit-budget-other-cost']) {
      await fillFormField(page, items['edit-budget-other-cost'], 'edit-budget-other-cost')
    }

    if (items['edit-muu-huomioitava-panostus']) {
      thisItem = items['edit-muu-huomioitava-panostus'];
      await fillInputField(
        thisItem.value ?? '',
        thisItem.selector ?? {
          type: 'data-drupal-selector',
          name: 'data-drupal-selector',
          value: 'edit-muu-huomioitava-panostus',
        },
        page,
        'edit-muu-huomioitava-panostus'
      );
    }

  },
  'lisatiedot_ja_liitteet': async (page: Page, {items}: FormPage) => {

    if (items['edit-additional-information']) {
      await page.getByRole('textbox', {name: 'Lisätiedot'})
        .fill(items['edit-additional-information'].value ?? '');
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

test.describe('KUVAPROJ(48)', () => {
  let page: Page;

  const profileType = 'private_person';
  const formId = '48';

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

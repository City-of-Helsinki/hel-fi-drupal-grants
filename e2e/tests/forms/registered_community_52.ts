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
import {deleteDraftApplication} from "../../utils/deletion_helpers";

const profileType = 'registered_community';
const formId = '52';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotRegisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-acting-year']) {
      await page.locator('#edit-acting-year')
        .selectOption(items['edit-acting-year'].value ?? '');
    }

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    if (items['edit-compensation-purpose']) {
      await page.locator('#edit-compensation-purpose')
        .fill(items['edit-compensation-purpose'].value ?? '');
    }

    // Muut samaan tarkoitukseen myönnetyt avustukset

    if (items['edit-benefits-loans']) {
      await page.locator('#edit-benefits-loans')
        .fill(items['edit-benefits-loans'].value ?? '');
    }

    if (items['edit-benefits-premises']) {
      await page.locator('#edit-benefits-premises')
        .fill(items['edit-benefits-premises'].value ?? '');
    }

    if (items['edit-compensation-boolean-1']) {
      await page.locator('#edit-compensation-boolean')
        .getByText(items['edit-compensation-boolean-1'].value ?? '').click();
    }

    if (items['edit-compensation-explanation']) {
      await page.locator('#edit-compensation-explanation')
        .fill(items['edit-compensation-explanation'].value ?? '');
    }

  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-business-purpose']) {
      await page.locator('#edit-business-purpose')
        .fill(items['edit-business-purpose'].value ?? '');
    }

    if (items['edit-community-practices-business-0']) {
      await page.locator('#edit-community-practices-business')
        .getByText(items['edit-community-practices-business-0'].value ?? '').click();
    }

    if (items['edit-toimintapaikka-items-0-item-location']) {
      await page.locator('#edit-toimintapaikka-items-0-item-location')
        .fill(items['edit-toimintapaikka-items-0-item-location'].value ?? '');
    }

    if (items['edit-toimintapaikka-items-0-item-streetaddress']) {
      await page.locator('#edit-toimintapaikka-items-0-item-streetaddress')
        .fill(items['edit-toimintapaikka-items-0-item-streetaddress'].value ?? '');
    }

    if (items['edit-toimintapaikka-items-0-item-postcode']) {
      await page.locator('#edit-toimintapaikka-items-0-item-postcode')
        .fill(items['edit-toimintapaikka-items-0-item-postcode'].value ?? '');
    }

    if (items['edit-toimintapaikka-items-0-item-studentcount']) {
      await fillInputField(
        items['edit-toimintapaikka-items-0-item-studentcount'].value ?? '',
        items['edit-toimintapaikka-items-0-item-studentcount'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-toimintapaikka-items-0-item-studentcount',
        },
        page,
        'edit-toimintapaikka-items-0-item-studentcount'
      );
    }

    if (items['edit-toimintapaikka-items-0-item-specialstudents']) {
      await fillInputField(
        items['edit-toimintapaikka-items-0-item-specialstudents'].value ?? '',
        items['edit-toimintapaikka-items-0-item-specialstudents'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-toimintapaikka-items-0-item-specialstudents',
        },
        page,
        'edit-toimintapaikka-items-0-item-specialstudents'
      );
    }

    if (items['edit-toimintapaikka-items-0-item-groupcount']) {
      await fillInputField(
        items['edit-toimintapaikka-items-0-item-groupcount'].value ?? '',
        items['edit-toimintapaikka-items-0-item-groupcount'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-toimintapaikka-items-0-item-groupcount',
        },
        page,
        'edit-toimintapaikka-items-0-item-groupcount'
      );
    }

    if (items['edit-toimintapaikka-items-0-item-specialgroups']) {
      await fillInputField(
        items['edit-toimintapaikka-items-0-item-specialgroups'].value ?? '',
        items['edit-toimintapaikka-items-0-item-specialgroups'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-toimintapaikka-items-0-item-specialgroups',
        },
        page,
        'edit-toimintapaikka-items-0-item-specialgroups'
      );
    }

    if (items['edit-toimintapaikka-items-0-item-personnelcount']) {
      await fillInputField(
        items['edit-toimintapaikka-items-0-item-personnelcount'].value ?? '',
        items['edit-toimintapaikka-items-0-item-personnelcount'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-toimintapaikka-items-0-item-personnelcount',
        },
        page,
        'edit-toimintapaikka-items-0-item-personnelcount'
      );
    }

    if (items['edit-toimintapaikka-items-0-item-free-0']) {
      await page.locator('#edit-toimintapaikka-items-0-item-free')
        .getByText('Ei').click();
    }

    if (items['edit-toimintapaikka-items-0-item-totalrent']) {
      await fillInputField(
        items['edit-toimintapaikka-items-0-item-totalrent'].value ?? '',
        items['edit-toimintapaikka-items-0-item-totalrent'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-toimintapaikka-items-0-item-totalrent',
        },
        page,
        'edit-toimintapaikka-items-0-item-totalrent'
      );
    }

    if (items['edit-toimintapaikka-items-0-item-renttimebegin']) {
      await page.locator('#edit-toimintapaikka-items-0-item-renttimebegin')
        .fill(items['edit-toimintapaikka-items-0-item-renttimebegin'].value ?? '');
    }

    if (items['edit-toimintapaikka-items-0-item-renttimeend']) {
      await page.locator('#edit-toimintapaikka-items-0-item-renttimeend')
        .fill(items['edit-toimintapaikka-items-0-item-renttimeend'].value ?? '');
    }

  },
  '4_talous': async (page: Page, {items}: FormPage) => {

    if (items['edit-tulot-customerfees']) {
      await fillInputField(
        items['edit-tulot-customerfees'].value ?? '',
        items['edit-tulot-customerfees'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-tulot-customerfees',
        },
        page,
        'edit-tulot-customerfees'
      );
    }

    if (items['edit-tulot-donations']) {
      await fillInputField(
        items['edit-tulot-donations'].value ?? '',
        items['edit-tulot-donations'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-tulot-donations',
        },
        page,
        'edit-tulot-donations'
      );
    }

    if (items['edit-muut-avustukset-field-items-0-item-label']) {
      await page.locator('#edit-muut-avustukset-field-items-0-item-label')
        .fill(items['edit-muut-avustukset-field-items-0-item-label'].value ?? '');
    }

    if (items['edit-muut-avustukset-field-items-0-item-value']) {
      await fillInputField(
        items['edit-muut-avustukset-field-items-0-item-value'].value ?? '',
        items['edit-muut-avustukset-field-items-0-item-value'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-avustukset-field-items-0-item-value',
        },
        page,
        'edit-muut-avustukset-field-items-0-item-value'
      );
    }

    if (items['edit-henkilostomenot-ja-vuokrat-salaries']) {
      await fillInputField(
        items['edit-henkilostomenot-ja-vuokrat-salaries'].value ?? '',
        items['edit-henkilostomenot-ja-vuokrat-salaries'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-henkilostomenot-ja-vuokrat-salaries',
        },
        page,
        'edit-henkilostomenot-ja-vuokrat-salaries'
      );
    }

    if (items['edit-henkilostomenot-ja-vuokrat-personnelsidecosts']) {
      await fillInputField(
        items['edit-henkilostomenot-ja-vuokrat-personnelsidecosts'].value ?? '',
        items['edit-henkilostomenot-ja-vuokrat-personnelsidecosts'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-henkilostomenot-ja-vuokrat-personnelsidecosts',
        },
        page,
        'edit-henkilostomenot-ja-vuokrat-personnelsidecosts'
      );
    }

    if (items['edit-henkilostomenot-ja-vuokrat-rentsum']) {
      await fillInputField(
        items['edit-henkilostomenot-ja-vuokrat-rentsum'].value ?? '',
        items['edit-henkilostomenot-ja-vuokrat-rentsum'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-henkilostomenot-ja-vuokrat-rentsum',
        },
        page,
        'edit-henkilostomenot-ja-vuokrat-rentsum'
      );
    }

    if (items['edit-muut-menot-4-items-0-item-label']) {
      await page.locator('#edit-muut-menot-4-items-0-item-label')
        .fill(items['edit-muut-menot-4-items-0-item-label'].value ?? '');
    }

    if (items['edit-muut-menot-4-items-0-item-value']) {
      await fillInputField(
        items['edit-muut-menot-4-items-0-item-value'].value ?? '',
        items['edit-muut-menot-4-items-0-item-value'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-menot-4-items-0-item-value',
        },
        page,
        'edit-muut-menot-4-items-0-item-value'
      );
    }

    if (items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-snacks']) {
      await fillInputField(
        items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-snacks'].value ?? '',
        items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-snacks'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-snacks',
        },
        page,
        'edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-snacks'
      );
    }

    if (items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-cleaning']) {
      await fillInputField(
        items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-cleaning'].value ?? '',
        items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-cleaning'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-cleaning',
        },
        page,
        'edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-cleaning'
      );
    }

    if (items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-premisesservice']) {
      await fillInputField(
        items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-premisesservice'].value ?? '',
        items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-premisesservice'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-premisesservice',
        },
        page,
        'edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-premisesservice'
      );
    }

    if (items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-travelcosts']) {
      await fillInputField(
        items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-travelcosts'].value ?? '',
        items['edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-travelcosts'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-travelcosts',
        },
        page,
        'edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-travelcosts'
      );
    }

    if (items['edit-muut-palveluiden-ostot-2-items-0-item-label']) {
      await page.locator('#edit-muut-palveluiden-ostot-2-items-0-item-label')
        .fill(items['edit-muut-palveluiden-ostot-2-items-0-item-label'].value ?? '');
    }

    if (items['edit-muut-palveluiden-ostot-2-items-0-item-value']) {
      await fillInputField(
        items['edit-muut-palveluiden-ostot-2-items-0-item-value'].value ?? '',
        items['edit-muut-palveluiden-ostot-2-items-0-item-value'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-palveluiden-ostot-2-items-0-item-value',
        },
        page,
        'edit-muut-palveluiden-ostot-2-items-0-item-value'
      );
    }

    if (items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-snacks']) {
      await fillInputField(
        items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-snacks'].value ?? '',
        items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-snacks'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-aineet-tarvikkeet-ja-tavarat-2-snacks',
        },
        page,
        'edit-muut-aineet-tarvikkeet-ja-tavarat-2-snacks'
      );
    }

    if (items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-heating']) {
      await fillInputField(
        items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-heating'].value ?? '',
        items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-heating'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-aineet-tarvikkeet-ja-tavarat-2-heating',
        },
        page,
        'edit-muut-aineet-tarvikkeet-ja-tavarat-2-heating'
      );
    }

    if (items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-water']) {
      await fillInputField(
        items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-water'].value ?? '',
        items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-water'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-aineet-tarvikkeet-ja-tavarat-2-water',
        },
        page,
        'edit-muut-aineet-tarvikkeet-ja-tavarat-2-water'
      );
    }

    if (items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-electricity']) {
      await fillInputField(
        items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-electricity'].value ?? '',
        items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-electricity'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-aineet-tarvikkeet-ja-tavarat-2-electricity',
        },
        page,
        'edit-muut-aineet-tarvikkeet-ja-tavarat-2-electricity'
      );
    }

    if (items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-supplies']) {
      await fillInputField(
        items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-supplies'].value ?? '',
        items['edit-muut-aineet-tarvikkeet-ja-tavarat-2-supplies'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-aineet-tarvikkeet-ja-tavarat-2-supplies',
        },
        page,
        'edit-muut-aineet-tarvikkeet-ja-tavarat-2-supplies'
      );
    }

    if (items['edit-muut-menot-tarvikkeet-items-0-item-label']) {
      await page.locator('#edit-muut-menot-tarvikkeet-items-0-item-label')
        .fill(items['edit-muut-menot-tarvikkeet-items-0-item-label'].value ?? '');
    }

    if (items['edit-muut-menot-tarvikkeet-items-0-item-value']) {
      await fillInputField(
        items['edit-muut-menot-tarvikkeet-items-0-item-value'].value ?? '',
        items['edit-muut-menot-tarvikkeet-items-0-item-value'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-menot-tarvikkeet-items-0-item-value',
        },
        page,
        'edit-muut-menot-tarvikkeet-items-0-item-value'
      );
    }

    if (items['edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-admin']) {
      await fillInputField(
        items['edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-admin'].value ?? '',
        items['edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-admin'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-admin',
        },
        page,
        'edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-admin'
      );
    }

    if (items['edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-accounting']) {
      await fillInputField(
        items['edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-accounting'].value ?? '',
        items['edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-accounting'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-accounting',
        },
        page,
        'edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-accounting'
      );
    }

    if (items['edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-health']) {
      await fillInputField(
        items['edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-health'].value ?? '',
        items['edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-health'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-health',
        },
        page,
        'edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-health'
      );
    }

    if (items['edit-muut-menot-2-items-0-item-label']) {
      await page.locator('#edit-muut-menot-2-items-0-item-label')
        .fill(items['edit-muut-menot-2-items-0-item-label'].value ?? '');
    }

    if (items['edit-muut-menot-2-items-0-item-value']) {
      await fillInputField(
        items['edit-muut-menot-2-items-0-item-value'].value ?? '',
        items['edit-muut-menot-2-items-0-item-value'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-menot-2-items-0-item-value',
        },
        page,
        'edit-muut-menot-2-items-0-item-value'
      );
    }

    if (items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-salaries']) {
      await fillInputField(
        items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-salaries'].value ?? '',
        items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-salaries'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-salaries',
        },
        page,
        'edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-salaries'
      );
    }

    if (items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-personnelsidecosts']) {
      await fillInputField(
        items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-personnelsidecosts'].value ?? '',
        items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-personnelsidecosts'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-personnelsidecosts',
        },
        page,
        'edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-personnelsidecosts'
      );
    }

    if (items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-rentsum']) {
      await fillInputField(
        items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-rentsum'].value ?? '',
        items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-rentsum'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-rentsum',
        },
        page,
        'edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-rentsum'
      );
    }

    if (items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-materials']) {
      await fillInputField(
        items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-materials'].value ?? '',
        items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-materials'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-materials',
        },
        page,
        'edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-materials'
      );
    }

    if (items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-services']) {
      await fillInputField(
        items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-services'].value ?? '',
        items['edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-services'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-services',
        },
        page,
        'edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-services'
      );
    }

    if (items['edit-muut-menot-3-items-0-item-label']) {
      await page.locator('#edit-muut-menot-3-items-0-item-label')
        .fill(items['edit-muut-menot-3-items-0-item-label'].value ?? '');
    }

    if (items['edit-muut-menot-3-items-0-item-value']) {
      await fillInputField(
        items['edit-muut-menot-3-items-0-item-value'].value ?? '',
        items['edit-muut-menot-3-items-0-item-value'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-muut-menot-3-items-0-item-value',
        },
        page,
        'edit-muut-menot-3-items-0-item-value'
      );
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

    if (items['edit-vuokrasopimus-haettaessa-vuokra-avustusta-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-vuokrasopimus-haettaessa-vuokra-avustusta-attachment-upload'].selector?.value ?? '',
        items['edit-vuokrasopimus-haettaessa-vuokra-avustusta-attachment-upload'].selector?.resultValue ?? '',
        items['edit-vuokrasopimus-haettaessa-vuokra-avustusta-attachment-upload'].value
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

test.describe('KASKOIPTOIM(52)', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()

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
    if (obj.viewPageSkipValidation) continue;
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
      await deleteDraftApplication(
        key,
        page,
        obj,
        storedata
      );
    });
  }


});

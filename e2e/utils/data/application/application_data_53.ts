import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PATH_TO_TEST_PDF} from "../../helpers";
import {createFormData} from "../../form_helpers";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_53: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-kasko-ip-lisa-form',
  formPath: '/fi/form/kasko-ip-lisa',
  formPages: {
    "1_hakijan_tiedot": {
      items: {
        "edit-email": {
          value: 'haloo@haloo.fi',
          // faker not working
        },
        "edit-contact-person": {
          value: faker.person.fullName(),
        },
        "edit-contact-person-phone-number": {
          value: faker.phone.number(),
        },
        "edit-bank-account-account-number-select": {
          role: 'select',
          value: 'use-random-value',
        },
        "edit-community-address-community-address-select": {
          value: '',
        },
        "edit-community-officials-items-0-item-community-officials-select": {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: 'community-officials-selector',
            value: '#edit-community-officials-items-0-item-community-officials-select',
          },
          value: '',
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '2_avustustiedot',
          }
        },
      },
    },
    "2_avustustiedot": {
      items: {
        "edit-acting-year": {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: 'bank-account-selector',
            value: '#edit-acting-year',
          },
          value: '2024',
        },
        "edit-subventions-items-0-amount": {
          value: '5709,98',
        },
        "edit-lyhyt-kuvaus-haettavan-haettavien-avustusten-kayttotarkoituksist": {
          value: faker.lorem.sentences(4),
        },
        "edit-alkaen": {
          role: 'input',
          value: '2023-09-23',
        },
        "edit-paattyy": {
          role: 'input',
          value: '2023-11-30',
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'lisatiedot_ja_liitteet',
          }
        },
      },
    },
    "lisatiedot_ja_liitteet": {
      items: {
        "edit-additional-information": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        'edit-muu-liite-items-0-item-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[muu_liite_items_0__item__attachment]"]',
            resultValue: '.form-item-muu-liite-items-0--item--attachment a',
          },
          value: PATH_TO_TEST_PDF,
        },
        'edit-muu-liite-items-0-item-description': {
          role: 'input',
          value: faker.lorem.sentences(1),
        },
        "edit-extra-info": {
          role: 'input',
          value: faker.lorem.sentences(2),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'webform_preview',
          }
        },
      },
    },
    "webform_preview": {
      items: {
        "accept_terms_1": {
          role: 'checkbox',
          value: "1",
        },
        "sendbutton": {
          role: 'button',
          value: 'submit-form',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-submit',
          }
        },
      },
    },
  },
  expectedErrors: {},
  expectedDestination: "/fi/hakemus/kasko_ip_lisa/",
}

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values from 1st page',
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-bank-account-account-number-select',
        'edit-email',
        'edit-contact-person',
        'edit-contact-person-phone-number',
        'edit-community-address-community-address-select',
      ],
    },
    '2_avustustiedot': {
      items: {},
      itemsToRemove: [
        'edit-acting-year',
        'edit-subventions-items-0-amount',
        'edit-lyhyt-kuvaus-haettavan-haettavien-avustusten-kayttotarkoituksist',
        'edit-alkaen',
        'edit-paattyy'
      ],
    },
    'webform_preview': {
      items: {
        "sendbutton": {
          role: 'button',
          value: 'save-draft',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-draft',
          }
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite kenttä on pakollinen.',
    'edit-contact-person': 'Virhe sivulla 1. Hakijan tiedot: Yhteyshenkilö kenttä on pakollinen.',
    'edit-contact-person-phone-number': 'Virhe sivulla 1. Hakijan tiedot: Puhelinnumero kenttä on pakollinen.',
    'edit-community-address-community-address-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse osoite kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-lyhyt-kuvaus-haettavan-haettavien-avustusten-kayttotarkoituksist': 'Virhe sivulla 2. Avustustiedot: Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista kenttä on pakollinen.',
    'edit-alkaen': 'Virhe sivulla 2. Avustustiedot: Alkaen kenttä on pakollinen.',
    'edit-paattyy': 'Virhe sivulla 2. Avustustiedot: Päättyy kenttä on pakollinen.',
  },
};

const saveDraft: FormDataWithRemoveOptionalProps = {
  title: 'Safe to draft and verify data',
  formPages: {
    'webform_preview': {
      items: {
        "sendbutton": {
          role: 'button',
          value: 'save-draft',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-draft',
          }
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {},
};


const registeredCommunityApplications_53 = {
  success: baseFormRegisteredCommunity_53,
  draft: createFormData(baseFormRegisteredCommunity_53, saveDraft),
  missing_values: createFormData(baseFormRegisteredCommunity_53, missingValues),
}

export {
  registeredCommunityApplications_53
}

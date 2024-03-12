import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PATH_MUU_LIITE} from "../../helpers";
import {createFormData} from "../../form_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatNumber,
} from "../../view_page_formatters";
import {PROFILE_INPUT_DATA} from "../profile_input_data";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_58: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-liikunta-suunnistuskartta-avustu-form',
  formPath: '/fi/form/liikunta-suunnistuskartta-avustu',
  formPages: {
    "1_hakijan_tiedot": {
      items: {
        "edit-email": {
          value: faker.internet.email(),
          viewPageFormatter: viewPageFormatLowerCase,
        },
        "edit-contact-person": {
          value: faker.person.fullName(),
        },
        "edit-contact-person-phone-number": {
          value: faker.phone.number(),
        },
        "edit-bank-account-account-number-select": {
          role: 'select',
          value: PROFILE_INPUT_DATA.iban,
          viewPageSelector: '.form-item-bank-account',
        },
        "edit-community-address-community-address-select": {
          value: `${PROFILE_INPUT_DATA.address}, ${PROFILE_INPUT_DATA.zipCode}, ${PROFILE_INPUT_DATA.city}`,
          viewPageSelector: '.form-item-community-address',
          viewPageFormatter: viewPageFormatAddress
        },
        "edit-community-officials-items-0-item-community-officials-select": {
          role: 'select',
          viewPageSelector: '.form-item-community-officials',
          value: PROFILE_INPUT_DATA.communityOfficial,
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '2_avustustiedot',
          },
          viewPageSkipValidation: true,
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
        "edit-orienteering-maps-items-0-item-mapname": {
          value: faker.lorem.sentences(4),
          viewPageSelector: '.form-item-orienteering-maps',
        },
        "edit-orienteering-maps-items-0-item-size": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-orienteering-maps',
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-orienteering-maps-items-0-item-voluntaryhours": {
          value: faker.number.int({min: 30, max: 50}).toString(),
          viewPageSelector: '.form-item-orienteering-maps',
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-orienteering-maps-items-0-item-cost": {
          value: faker.number.int({min: 100, max: 200}).toString(),
          viewPageSelector: '.form-item-orienteering-maps',
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-orienteering-maps-items-0-item-othercompensations": {
          value: faker.number.int({min: 10, max: 50}).toString(),
          viewPageSelector: '.form-item-orienteering-maps',
          viewPageFormatter: viewPageFormatNumber,
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'lisatiedot_ja_liitteet',
          },
          viewPageSkipValidation: true,
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
          value: PATH_MUU_LIITE,
          viewPageSelector: '.form-item-muu-liite',
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-muu-liite-items-0-item-description': {
          role: 'input',
          value: faker.lorem.sentences(1),
          viewPageSelector: '.form-item-muu-liite',
        },
        "edit-extra-info": {
          value: faker.lorem.sentences(2),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'webform_preview',
          },
          viewPageSkipValidation: true,
        },
      },
    },
    "webform_preview": {
      items: {
        "accept_terms_1": {
          role: 'checkbox',
          value: "1",
          viewPageSkipValidation: true,
        },
        "sendbutton": {
          role: 'button',
          value: 'save-draft',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-draft',
          },
          viewPageSkipValidation: true,
        },
      },
    },
  },
  expectedErrors: {},
  expectedDestination: "/fi/hakemus/liikunta_suunnistuskartta_avustu/",
}

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values',
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
        'edit-orienteering-maps-items-0-item-mapname',
      ],
    },
    'webform_preview': {
      items: {},
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite kenttä on pakollinen.',
    'edit-contact-person': 'Virhe sivulla 1. Hakijan tiedot: Yhteyshenkilö kenttä on pakollinen.',
    'edit-contact-person-phone-number': 'Virhe sivulla 1. Hakijan tiedot: Puhelinnumero kenttä on pakollinen.',
    'edit-community-address': 'Virhe sivulla 1. Hakijan tiedot: Yhteisön osoite kenttä on pakollinen.',
    'edit-community-address-community-address-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse osoite kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-orienteering-maps-items-0-item-mapname': 'Virhe sivulla 2. Avustustiedot: Kartan nimi, sijainti ja karttatyyppi kenttä on pakollinen.',
  },
};

const wrongValues: FormDataWithRemoveOptionalProps = {
  title: 'Wrong values',
  formPages: {
    '1_hakijan_tiedot': {
      items: {
        "edit-email": {
          role: 'input',
          value: 'ääkkösiävaa',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-email',
          },
          viewPageSkipValidation: true,
        },
      },
      itemsToRemove: [],
    },
    "2_avustustiedot": {
      items: {
        "edit-orienteering-maps-items-0-item-voluntaryhours": {
          value: '15',
          viewPageSelector: '.form-item-orienteering-maps',
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-orienteering-maps-items-0-item-cost": {
          value: '100',
          viewPageSelector: '.form-item-orienteering-maps',
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-orienteering-maps-items-0-item-othercompensations": {
          value: '2000',
          viewPageSelector: '.form-item-orienteering-maps',
          viewPageFormatter: viewPageFormatNumber,
        },
      },
    },
    'webform_preview': {
      items: {},
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite ääkkösiävaa ei kelpaa.',
    'edit-orienteering-maps-items-0-item-othercompensations': 'Virhe sivulla 2. Avustustiedot: Arvo ei voi olla suurempi kuin "Talkootyö tuntia" ja "Kustannukset euroa" kenttien summa.',
  },
};

const sendApplication: FormDataWithRemoveOptionalProps = {
  title: 'Send to AVUS2',
  formPages: {
    'webform_preview': {
      items: {
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
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {},
};

const registeredCommunityApplications_58 = {
  draft: baseFormRegisteredCommunity_58,
  missing_values: createFormData(baseFormRegisteredCommunity_58, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_58, wrongValues),
  success: createFormData(baseFormRegisteredCommunity_58, sendApplication),
}

export {
  registeredCommunityApplications_58
}

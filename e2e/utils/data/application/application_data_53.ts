import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PROFILE_INPUT_DATA} from "../profile_input_data";
import {ATTACHMENTS} from "../attachment_data";
import {createFormData} from "../../form_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatDate,
  viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatCurrency
} from "../../view_page_formatters";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_53: FormData = {
  title: 'Save as draft.',
  formSelector: 'webform-submission-kasko-ip-lisa-form',
  formPath: '/fi/form/kasko-ip-lisa',
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
        "edit-subventions-items-0-amount": {
          value: '5709,98',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatCurrency
        },
        "edit-lyhyt-kuvaus-haettavan-haettavien-avustusten-kayttotarkoituksist": {
          value: faker.lorem.sentences(4),
        },
        "edit-alkaen": {
          role: 'input',
          value: '2023-09-23',
          viewPageFormatter: viewPageFormatDate,
        },
        "edit-paattyy": {
          role: 'input',
          value: '2023-11-30',
          viewPageFormatter: viewPageFormatDate,
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'lisatiedot_ja_liitteet',
          },
          viewPageSkipValidation: true
        },
      },
    },
    "lisatiedot_ja_liitteet": {
      items: {
        "edit-additional-information": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-muu-liite": {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-muu-liite-add-submit',
              resultValue: 'edit-muu-liite-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'fileupload',
                  selector: {
                    type: 'locator',
                    name: 'data-drupal-selector',
                    value: '[name="files[muu_liite_items_[INDEX]__item__attachment]"]',
                    resultValue: '.form-item-muu-liite-items-[INDEX]--item--attachment a',
                  },
                  value: ATTACHMENTS.MUU_LIITE,
                  viewPageFormatter: viewPageFormatFilePath
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muu-liite-items-[INDEX]-item-description',
                  },
                  value: faker.lorem.sentences(1),
                },
              ],
            },
          },
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
          },
          viewPageSkipValidation: true
        },
      },
    },
    "webform_preview": {
      items: {
        "accept_terms_1": {
          role: 'checkbox',
          value: "1",
          viewPageSkipValidation: true
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
  expectedDestination: "/fi/hakemus/kasko_ip_lisa/",
}

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values',
  viewPageSkipValidation: true,
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
  },
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite kenttä on pakollinen.',
    'edit-contact-person': 'Virhe sivulla 1. Hakijan tiedot: Yhteyshenkilö kenttä on pakollinen.',
    'edit-contact-person-phone-number': 'Virhe sivulla 1. Hakijan tiedot: Puhelinnumero kenttä on pakollinen.',
    'edit-community-address': 'Virhe sivulla 1. Hakijan tiedot: Yhteisön osoite kenttä on pakollinen.',
    'edit-community-address-community-address-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse osoite kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-lyhyt-kuvaus-haettavan-haettavien-avustusten-kayttotarkoituksist': 'Virhe sivulla 2. Avustustiedot: Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista kenttä on pakollinen.',
    'edit-alkaen': 'Virhe sivulla 2. Avustustiedot: Alkaen kenttä on pakollinen.',
    'edit-paattyy': 'Virhe sivulla 2. Avustustiedot: Päättyy kenttä on pakollinen.',
  },
};

const wrongValues: FormDataWithRemoveOptionalProps = {
  title: 'Wrong values',
  viewPageSkipValidation: true,
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
          }
        },
      },
      itemsToRemove: [],
    },
  },
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite ääkkösiävaa ei kelpaa.',
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
          },
          viewPageSkipValidation: true,
        },
      },
      itemsToRemove: [],
    },
  },
  expectedErrors: {},
};


const registeredCommunityApplications_53 = {
  draft: baseFormRegisteredCommunity_53,
  missing_values: createFormData(baseFormRegisteredCommunity_53, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_53, wrongValues),
  success: createFormData(baseFormRegisteredCommunity_53, sendApplication),
}

export {
  registeredCommunityApplications_53
}

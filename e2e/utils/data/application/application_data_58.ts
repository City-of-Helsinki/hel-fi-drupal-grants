import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PROFILE_INPUT_DATA} from "../profile_input_data";
import {ATTACHMENTS} from "../attachment_data";
import {createFormData} from "../../form_data_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatCurrency,
  viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatNumber,
} from "../../view_page_formatters";
import {getFakeEmailAddress} from "../../field_helpers";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_58: FormData = {
  title: 'Save as draft.',
  formSelector: 'webform-submission-liikunta-suunnistuskartta-avustu-form',
  formPath: '/fi/form/liikunta-suunnistuskartta-avustu',
  formPages: {
    "1_hakijan_tiedot": {
      items: {
        "edit-email": {
          value: getFakeEmailAddress(),
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
            name: '',
            value: '#edit-acting-year',
          },
          viewPageSkipValidation: true,
        },
        'edit-orienteering-maps': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-orienteering-maps-add-submit',
              resultValue: 'edit-orienteering-maps-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-mapname',
                  },
                  value: faker.lorem.sentences(4),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-size',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-voluntaryhours',
                  },
                  value: faker.number.int({min: 30, max: 50}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-cost',
                  },
                  value: faker.number.int({min: 100, max: 200}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-othercompensations',
                  },
                  value: faker.number.int({min: 10, max: 50}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-mapname',
                  },
                  value: faker.lorem.sentences(4),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-size',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-voluntaryhours',
                  },
                  value: faker.number.int({min: 30, max: 50}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-cost',
                  },
                  value: faker.number.int({min: 100, max: 200}).toString(),
                  viewPageFormatter: viewPageFormatCurrency,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-othercompensations',
                  },
                  value: faker.number.int({min: 10, max: 50}).toString(),
                  viewPageFormatter: viewPageFormatCurrency,
                },
              ],
            },
            expectedErrors: {}
          },
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
        'edit-orienteering-maps': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-orienteering-maps-add-submit',
              resultValue: 'edit-orienteering-maps-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-mapname',
                  },
                  value: faker.lorem.sentences(4),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-size',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-voluntaryhours',
                  },
                  value: '15',
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-cost',
                  },
                  value: '100',
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-orienteering-maps-items-[INDEX]-item-othercompensations',
                  },
                  value: '2000',
                  viewPageFormatter: viewPageFormatNumber,
                },
              ],
            },
            expectedErrors: {}
          },
        },
      },
    },
    'webform_preview': {
      items: {},
      itemsToRemove: [],
    },
  },
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: ääkkösiävaa ei ole kelvollinen sähköpostiosoite. Täytä sähköpostiosoite muodossa user@example.com.',
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
          },
          viewPageSkipValidation: true
        },
      },
      itemsToRemove: [],
    },
  },
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

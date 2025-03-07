import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PROFILE_INPUT_DATA} from "../profile_input_data";
import {ATTACHMENTS} from "../attachment_data";
import {createFormData} from "../../form_data_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatBoolean,
  viewPageFormatCurrency,
  viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatNumber
} from "../../view_page_formatters";
import {getFakeEmailAddress} from "../../field_helpers";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_64: FormData = {
  title: 'Save as draft.',
  formSelector: 'webform-submission-asukasosallisuus-pienavustushake-form',
  formPath: '/fi/form/asukasosallisuus-pienavustushake',
  validateTooltips: true,
  formPages: {
    "1_hakijan_tiedot": {
      items: {
        "edit-email": {
          value: 'test@test.fi',
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
          viewPageFormatter: viewPageFormatAddress,
          printPageSkipValidation: true,
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
      tooltipsToValidate: [
        { aria_label: 'Sähköpostiosoite', message: 'Ilmoita sähköpostiosoite, johon tähän hakemukseen liittyvät viestit sekä herätteet osoitetaan ja jota luetaan aktiivisesti' },
        { aria_label: 'Valitse osoite', message: 'Jos haluat lisätä, poistaa tai muuttaa osoitetietoa tallenna hakemus luonnokseksi ja siirry ylläpitämään osoitetietoa omiin tietoihin.' },
        { aria_label: 'Valitse tilinumero', message: 'Jos haluat lisätä, poistaa tai muuttaa tilinumerotietoa tallenna hakemus luonnokseksi ja siirry ylläpitämään tilinumerotietoa omiin tietoihin.' },
        { aria_label: 'Valitse toiminnasta vastaavat henkilöt', message: 'Jos haluat lisätä, poistaa tai muuttaa henkilöitä tallenna hakemus luonnokseksi ja siirry ylläpitämään henkilöiden tietoja omiin tietoihin.' },
      ]
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
        "edit-subventions-items-0-amount": {
          value: '5709,98',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatCurrency
        },
        "edit-purpose": {
          value: faker.lorem.sentences(4),
        },
        "edit-benefits-loans": {
          value: faker.lorem.sentences(4),
        },
        "edit-benefits-premises": {
          value: faker.lorem.sentences(4),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '3_yhteison_tiedot',
          },
          viewPageSkipValidation: true,
        },
      },
      tooltipsToValidate: [
        { aria_label: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista', message: 'Kerro mitä tarkoitusta varten avustusta haetaan, erittele tarvittaessa eri käyttökohteet. Kerro myös mitä avustuksella on tarkoitus saada aikaiseksi ja millaisia tavoitteita avustettavaan toimintaan liittyy.' },
      ]
    },
    '3_yhteison_tiedot': {
      items: {
        "edit-community-practices-business-1": {
          value: "Ei",
          viewPageFormatter: viewPageFormatBoolean,
        },
        "edit-fee-person": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-fee-community": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-members-applicant-person-global": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-members-applicant-person-local": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-members-applicant-community-global": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-members-applicant-community-local": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
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
      tooltipsToValidate: [
        { aria_label: 'Toiminnan kuvaus', message: 'Tieto haetaan omat tiedot -osiosta' },
      ]
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
                  value: faker.location.zipCode(),
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
          viewPageSkipValidation: true,
        },
      },
    },
    "webform_preview": {
      items: {
        "accept_terms_1": {
          role: 'checkbox',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot',
              options: {
                exact: true
              }
            },
          },
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
  expectedDestination: "/fi/hakemus/asukasosallisuus_pienavustushake/",
}

/**
 * Basic form data.
 *
 * Private person.
 */
const baseFormPrivatePerson_64: FormData = createFormData(
  baseFormRegisteredCommunity_64,
  {
    validateTooltips: false,
    formPages: {
      "1_hakijan_tiedot": {
        items: {
          "edit-bank-account-account-number-select": {
            role: 'select',
            value: PROFILE_INPUT_DATA.iban,
            viewPageSelector: '.form-item-bank-account',
          },
          "edit-community-officials-items-0-item-community-officials-select": {
            viewPageSkipValidation: true,
          },
          "edit-email": {
            viewPageSkipValidation: true,
          },
          "edit-contact-person": {
            viewPageSkipValidation: true,
          },
          "edit-contact-person-phone-number": {
            viewPageSkipValidation: true,
          },
          "edit-community-address-community-address-select": {
            viewPageSkipValidation: true,
          },
        },
      },
    },
  }
);

/**
 * Basic form data.
 *
 * Unregistered community.
 */
const baseFormUnRegisteredCommunity_64: FormData = createFormData(
  baseFormRegisteredCommunity_64,
  {
    validateTooltips: false,
    formPages: {
      "1_hakijan_tiedot": {
        items: {
          "edit-bank-account-account-number-select": {
            role: 'select',
            value: PROFILE_INPUT_DATA.iban,
            viewPageSelector: '.form-item-bank-account',
          },
          "edit-community-officials-items-0-item-community-officials-select": {
            role: 'select',
            value: PROFILE_INPUT_DATA.communityOfficial,
            viewPageSelector: '.form-item-community-officials',
          },
          "edit-email": {
           viewPageSkipValidation: true,
          },
          "edit-contact-person": {
            viewPageSkipValidation: true,
          },
          "edit-contact-person-phone-number": {
            viewPageSkipValidation: true,
          },
          "edit-community-address-community-address-select": {
            viewPageSkipValidation: true,
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
    },
  }
);

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values',
  viewPageSkipValidation: true,
  validateTooltips: false,
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-bank-account-account-number-select',
        'edit-email',
        'edit-contact-person',
        'edit-contact-person-phone-number',
        'edit-community-address-community-address-select'
      ],
    },
    '2_avustustiedot': {
      items: {},
      itemsToRemove: [
        'edit-acting-year',
        'edit-subventions-items-0-amount',
        'edit-purpose'
      ],
    },
    '3_yhteison_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-community-practices-business-1',
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
    'edit-purpose': 'Virhe sivulla 2. Avustustiedot: Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista kenttä on pakollinen.',
    'edit-community-practices-business-1': 'Virhe sivulla 3. Yhteisön toiminta: Harjoittaako yhteisö liiketoimintaa kenttä on pakollinen.',
  },
};

const missingValuesPrivateUnregistered: FormDataWithRemoveOptionalProps = {
  title: 'Missing values',
  viewPageSkipValidation: true,
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-bank-account-account-number-select',
      ],
    },
    '2_avustustiedot': {
      items: {},
      itemsToRemove: [
        'edit-acting-year',
        'edit-subventions-items-0-amount',
        'edit-purpose'
      ],
    },
    '3_yhteison_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-community-practices-business-1',
      ],
    },
  },
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-purpose': 'Virhe sivulla 2. Avustustiedot: Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista kenttä on pakollinen.',
    'edit-community-practices-business-1': 'Virhe sivulla 3. Yhteisön toiminta: Harjoittaako yhteisö liiketoimintaa kenttä on pakollinen.',
  },
};

const wrongValues: FormDataWithRemoveOptionalProps = {
  title: 'Wrong values',
  viewPageSkipValidation: true,
  validateTooltips: false,
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
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: ääkkösiävaa ei ole kelvollinen sähköpostiosoite. Täytä sähköpostiosoite muodossa user@example.com.',
  },
};

const copyForm: FormDataWithRemoveOptionalProps = {
  title: 'Original copy form',
  testFormCopying: true,
  validatePrintPage: true,
  validateTooltips: false,
  formPages: {
    'lisatiedot_ja_liitteet': {
      items: {},
      itemsToRemove: [
        'edit-muu-liite',
      ],
    },
  },
  expectedErrors: {},
};

const sendApplication: FormDataWithRemoveOptionalProps = {
  title: 'Send to AVUS2',
  validateTooltips: false,
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

const registeredCommunityApplications_64 = {
  draft: baseFormRegisteredCommunity_64,
  copy: createFormData(baseFormRegisteredCommunity_64, copyForm),
  missing_values: createFormData(baseFormRegisteredCommunity_64, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_64, wrongValues),
  success: createFormData(baseFormRegisteredCommunity_64, sendApplication),
}

/**
 * All data for private persons' applications.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 */
const privatePersonApplications_64 = {
  draft: baseFormPrivatePerson_64,
  missing_values: createFormData(baseFormPrivatePerson_64, missingValuesPrivateUnregistered),
  success: createFormData(baseFormPrivatePerson_64, sendApplication),
}

/**
 * All data for unregistered community applications.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 */
const unRegisteredCommunityApplications_64 = {
  draft: baseFormUnRegisteredCommunity_64,
  missing_values: createFormData(baseFormUnRegisteredCommunity_64, missingValuesPrivateUnregistered),
  success: createFormData(baseFormUnRegisteredCommunity_64, sendApplication),
}

export {
  registeredCommunityApplications_64,
  privatePersonApplications_64,
  unRegisteredCommunityApplications_64
}

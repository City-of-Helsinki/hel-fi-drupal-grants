import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {
  PATH_YHTEISON_SAANNOT,
  PATH_TOIMINTASUUNNITELMA,
  PATH_TALOUSARVIO,
  PATH_MUU_LIITE,
} from "../../helpers";
import {createFormData} from "../../form_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatBoolean, viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatCurrency,
  viewPageFormatNumber, viewPageFormatDate
} from "../../view_page_formatters";
import {PROFILE_INPUT_DATA} from "../profile_input_data";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_62: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-nuorisotoiminta-projektiavustush-form',
  formPath: '/fi/form/nuorisotoiminta-projektiavustush',
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
        "edit-kenelle-haen-avustusta": {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: 'bank-account-selector',
            value: '#edit-kenelle-haen-avustusta',
          },
          value: 'Nuorisoyhdistys',
        },
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
        // muut samaan tarkoitukseen myönnetyt
        // muut samaan tarkoitukseen haetut
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '3_jasenet_tai_aktiiviset_osallistujat',
          }
        },
      },
    },
    "3_jasenet_tai_aktiiviset_osallistujat": {
      items: {
        "edit-jasenet-7-28": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-jasenet-7-28',
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-jasenet-kaikki": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'projektisuunnitelma',
          }
        },
      },
    },
    "projektisuunnitelma": {
      items: {
        "edit-projektin-nimi": {
          value: faker.lorem.words(4),
        },
        "edit-projektin-tavoitteet": {
          value: faker.lorem.sentences(4),
        },
        "edit-projektin-sisalto": {
          value: faker.lorem.sentences(4),
        },
        "edit-projekti-alkaa": {
          value: '2023-12-01',
          viewPageFormatter: viewPageFormatDate,
        },
        "edit-projekti-loppuu": {
          value: '2023-12-31',
          viewPageFormatter: viewPageFormatDate,
        },
        "edit-osallistujat-7-28": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-osallistujat-7-28',
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-osallistujat-kaikki": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-projektin-paikka-2": {
          value: faker.lorem.sentences(4),
          viewPageSelector: '.form-item-projektin-paikka-2',
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '6_talous',
          }
        },
      },
    },
    "6_talous": {
      items: {
        "edit-omarahoitusosuuden-kuvaus": {
          value: faker.lorem.sentences(4),
        },
        "edit-omarahoitusosuus": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatCurrency
        },
        "edit-budget-other-income-items-0-item-label": {
          value: faker.lorem.words(3),
          viewPageSelector: '.form-item-budget-other-income',
        },
        "edit-budget-other-income-items-0-item-value": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-other-income',
          viewPageFormatter: viewPageFormatCurrency
        },
        "edit-budget-other-cost-items-0-item-label": {
          value: faker.lorem.words(3),
          viewPageSelector: '.form-item-budget-other-cost',
        },
        "edit-budget-other-cost-items-0-item-value": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-other-cost',
          viewPageFormatter: viewPageFormatCurrency
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
          value: faker.lorem.sentences(3),
        },
        'edit-yhteison-saannot-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[yhteison_saannot_attachment]"]',
            resultValue: '.form-item-yhteison-saannot-attachment a',
          },
          value: PATH_YHTEISON_SAANNOT,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-projektisuunnitelma-liite-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[projektisuunnitelma_liite_attachment]"]',
            resultValue: '.form-item-projektisuunnitelma-liite-attachment a',
          },
          value: PATH_TOIMINTASUUNNITELMA,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-projektin-talousarvio-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[projektin_talousarvio_attachment]"]',
            resultValue: '.form-item-projektin-talousarvio-attachment a',
          },
          value: PATH_TALOUSARVIO,
          viewPageFormatter: viewPageFormatFilePath,
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
          }
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
          viewPageSkipValidation: true
        },
      },
    },
  },
  expectedErrors: {},
  expectedDestination: "/fi/hakemus/nuorisotoiminta_projektiavustush/",
}

/**
 * Basic form data for successful submit to Avus2.
 *
 * Unregistered community.
 */
const baseFormUnRegisteredCommunity_62: FormData = createFormData(
  baseFormRegisteredCommunity_62,
  {
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
    },
  }
);

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
        'edit-kenelle-haen-avustusta',
        'edit-acting-year',
        'edit-subventions-items-0-amount',
      ],
    },
    '3_jasenet_tai_aktiiviset_osallistujat': {
      items: {},
      itemsToRemove: [
        'edit-jasenet-7-28',
        'edit-jasenet-kaikki',
      ],
    },
    'projektisuunnitelma': {
      items: {},
      itemsToRemove: [],
    },
    '6_talous': {
      items: {},
      itemsToRemove: [
        'edit-projektin-nimi',
        'edit-projekti-alkaa',
        'edit-projekti-loppuu',
        'edit-osallistujat-7-28',
        'edit-osallistujat-kaikki'
      ],
    },
    'lisatiedot_ja_liitteet': {
      items: {},
      itemsToRemove: [
        'edit-yhteison-saannot-attachment-upload',
        'edit-projektisuunnitelma-liite-attachment-upload',
        'edit-projektin-talousarvio-attachment-upload',
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
    'edit-kenelle-haen-avustusta': 'Virhe sivulla 2. Avustustiedot: Kenelle haen avustusta kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-jasenet-7-28': 'Virhe sivulla 3. Jäsenet tai aktiiviset osallistujat: Kuinka monta 7-28 -vuotiasta helsinkiläistä jäsentä tai aktiivista osallistujaa nuorten toimintaryhmässä / yhdistyksessä / talokerhossa on? kenttä on pakollinen.',
    'edit-jasenet-kaikki': 'Virhe sivulla 3. Jäsenet tai aktiiviset osallistujat: Kuinka monta jäsentä tai aktiivista osallistujaa nuorten toimintaryhmässä / yhdistyksessä / talokerhossa on yhteensä? kenttä on pakollinen.',
    'edit-projektin-nimi': 'Virhe sivulla 4. Projektisuunnitelma: Projektin nimi kenttä on pakollinen.',
    'edit-projekti-alkaa': 'Virhe sivulla 4. Projektisuunnitelma: Projekti alkaa kenttä on pakollinen.',
    'edit-projekti-loppuu': 'Virhe sivulla 4. Projektisuunnitelma: Projekti loppuu kenttä on pakollinen.',
    'edit-osallistujat-7-28': 'Virhe sivulla 4. Projektisuunnitelma: Kuinka monta 7-28 -vuotiasta helsinkiläistä projektiin osallistuu? kenttä on pakollinen.',
    'edit-osallistujat-kaikki': 'Virhe sivulla 4. Projektisuunnitelma: Kuinka paljon projektin osallistujia on yhteensä? kenttä on pakollinen.',
    /*'edit-yhteison-saannot-attachment-upload': 'Virhe sivulla 6. Lisätiedot ja liitteet: Yhteisön säännöt ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-projektisuunnitelma-liite-attachment-upload': 'Virhe sivulla 6. Lisätiedot ja liitteet: Vahvistettu tilinpäätös (edelliseltä päättyneeltä tilikaudelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-projektin-talousarvio-attachment-upload': 'Virhe sivulla 6. Lisätiedot ja liitteet: Talousarvio (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',*/
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
          viewPageSkipValidation: true
        },
      },
      itemsToRemove: [],
    },
    '6_talous': {
      items: {},
      itemsToRemove: [
        'edit-budget-other-income-items-0-item-value',
        'edit-budget-other-cost-items-0-item-label'
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
          },
          viewPageSkipValidation: true
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite ääkkösiävaa ei kelpaa.',
    'edit-budget-other-income-items-0-item-value': 'Virhe sivulla 5. Talousarvio: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon',
    'edit-budget-other-cost-items-0-item-label': 'Virhe sivulla 5. Talousarvio: Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
  },
};

const wrongValuesUnregistered: FormDataWithRemoveOptionalProps = {
  title: 'Wrong values',
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: [],
    },
    '6_talous': {
      items: {},
      itemsToRemove: [
        'edit-budget-other-income-items-0-item-value',
        'edit-budget-other-cost-items-0-item-label'
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
          },
          viewPageSkipValidation: true
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-budget-other-income-items-0-item-value': 'Virhe sivulla 5. Talousarvio: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon',
    'edit-budget-other-cost-items-0-item-label': 'Virhe sivulla 5. Talousarvio: Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
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

const registeredCommunityApplications_62 = {
  draft: baseFormRegisteredCommunity_62,
  missing_values: createFormData(baseFormRegisteredCommunity_62, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_62, wrongValues),
  // success: createFormData(baseFormRegisteredCommunity_62, sendApplication),
}

/**
 * All data for unregistered community applications.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 */
const unRegisteredCommunityApplications_62 = {
  draft: baseFormUnRegisteredCommunity_62,
  missing_values: createFormData(baseFormUnRegisteredCommunity_62, missingValues),
  wrong_values: createFormData(baseFormUnRegisteredCommunity_62, wrongValuesUnregistered),
  // success: createFormData(baseFormUnRegisteredCommunity_62, sendApplication),
}

export {
  registeredCommunityApplications_62,
  unRegisteredCommunityApplications_62
}

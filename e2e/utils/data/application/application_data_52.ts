import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {
  PATH_YHTEISON_SAANNOT,
  PATH_VAHVISTETTU_TILINPAATOS,
  PATH_VAHVISTETTU_TOIMINTAKERTOMUS,
  PATH_VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS,
  PATH_VUOSIKOKOUKSEN_POYTAKIRJA,
  PATH_TOIMINTASUUNNITELMA,
  PATH_TALOUSARVIO,
  PATH_MUU_LIITE,
} from "../../helpers";
import {createFormData} from "../../form_helpers";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_52: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-kasvatus-ja-koulutus-toiminta-av-form',
  formPath: '/fi/form/kasvatus-ja-koulutus-toiminta-av',
  formPages: {
    "1_hakijan_tiedot": {
      items: {
        "edit-email": {
          value: faker.internet.email(),
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
        "edit-compensation-purpose": {
          value: faker.lorem.sentences(4),
        },
        // muut samaan tarkoitukseen myönnetyt
        "edit-benefits-loans": {
          value: faker.lorem.sentences(4),
        },
        "edit-benefits-premises": {
          value: faker.lorem.sentences(4),
        },
        "edit-compensation-boolean-1": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-compensation-boolean-1',
          },
          value: "Olen saanut Helsingin kaupungilta avustusta samaan käyttötarkoitukseen edellisenä vuonna.",
        },
        "edit-compensation-explanation": {
          value: faker.lorem.sentences(4),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '3_yhteison_tiedot',
          }
        },
      },
    },
    '3_yhteison_tiedot': {
      items: {
        "edit-business-purpose": {
          value: faker.lorem.sentences(4),
        },
        "edit-community-practices-business-0": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-community-practices-business-0',
          },
          value: "Ei",
        },
        "edit-fee-person": {
          value: '321,12',
        },
        "edit-fee-community": {
          value: '321,12',
        },
        "edit-toimintapaikka-items-0-item-location": {
          value: faker.lorem.words(2),
        },
        "edit-toimintapaikka-items-0-item-streetaddress": {
          value: faker.location.streetAddress(),
        },
        "edit-toimintapaikka-items-0-item-postcode": {
          value: faker.location.zipCode(),
        },
        "edit-toimintapaikka-items-0-item-studentcount": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-toimintapaikka-items-0-item-specialstudents": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-toimintapaikka-items-0-item-groupcount": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-toimintapaikka-items-0-item-specialgroups": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-toimintapaikka-items-0-item-personnelcount": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-toimintapaikka-items-0-item-free-0": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-toimintapaikka-items-0-item-free-0',
          },
          value: "Ei",
        },
        "edit-toimintapaikka-items-0-item-totalrent": {
          value: '321,12',
        },
        "edit-toimintapaikka-items-0-item-renttimebegin": {
          value: '2023-12-01',
        },
        "edit-toimintapaikka-items-0-item-renttimeend": {
          value: '2023-12-31',
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '4_talous',
          }
        },
      }
    },
    "4_talous": {
      items: {
        "edit-tulot-customerfees": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-tulot-donations": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-avustukset-field-items-0-item-label": {
          role: 'input',
          value: faker.lorem.sentence(15),
        },
        "edit-muut-avustukset-field-items-0-item-value": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-henkilostomenot-ja-vuokrat-salaries": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-henkilostomenot-ja-vuokrat-personnelsidecosts": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-henkilostomenot-ja-vuokrat-rentsum": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-menot-4-items-0-item-label": {
          role: 'input',
          value: faker.lorem.sentence(15),
        },
        "edit-muut-menot-4-items-0-item-value": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-snacks": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-cleaning": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-premisesservice": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-travelcosts": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-palveluiden-ostot-2-items-0-item-label": {
          role: 'input',
          value: faker.lorem.sentence(15),
        },
        "edit-muut-palveluiden-ostot-2-items-0-item-value": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-aineet-tarvikkeet-ja-tavarat-2-snacks": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-aineet-tarvikkeet-ja-tavarat-2-heating": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-aineet-tarvikkeet-ja-tavarat-2-water": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-aineet-tarvikkeet-ja-tavarat-2-electricity": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-aineet-tarvikkeet-ja-tavarat-2-supplies": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-menot-tarvikkeet-items-0-item-label": {
          role: 'input',
          value: faker.lorem.sentence(15),
        },
        "edit-muut-menot-tarvikkeet-items-0-item-value": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-admin": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-accounting": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-health": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-menot-2-items-0-item-label": {
          role: 'input',
          value: faker.lorem.sentence(15),
        },
        "edit-muut-menot-2-items-0-item-value": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-salaries": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-personnelsidecosts": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-rentsum": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-materials": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-services": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muut-menot-3-items-0-item-label": {
          role: 'input',
          value: faker.lorem.sentence(15),
        },
        "edit-muut-menot-3-items-0-item-value": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
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
        },
        'edit-vahvistettu-tilinpaatos-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilinpaatos_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilinpaatos-attachment a',
          },
          value: PATH_VAHVISTETTU_TILINPAATOS,
        },
        'edit-vahvistettu-toimintakertomus-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_toimintakertomus_attachment]"]',
            resultValue: '.form-item-vahvistettu-toimintakertomus-attachment a',
          },
          value: PATH_VAHVISTETTU_TOIMINTAKERTOMUS,
        },
        'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilin_tai_toiminnantarkastuskertomus_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment a',
          },
          value: PATH_VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS,
        },
        'edit-toimintasuunnitelma-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[toimintasuunnitelma_attachment]"]',
            resultValue: '.form-item-toimintasuunnitelma-attachment a',
          },
          value: PATH_TOIMINTASUUNNITELMA,
        },
        'edit-vuokrasopimus-haettaessa-vuokra-avustusta-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vuokrasopimus_haettaessa_vuokra_avustusta__attachment]"]',
            resultValue: '.form-item-vuokrasopimus-haettaessa-vuokra-avustusta--attachment a',
          },
          value: PATH_MUU_LIITE,
        },
        'edit-talousarvio-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[talousarvio_attachment]"]',
            resultValue: '.form-item-talousarvio-attachment a',
          },
          value: PATH_TALOUSARVIO,
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
          value: 'save-draft',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-draft',
          }
        },
      },
    },
  },
  expectedErrors: {},
  expectedDestination: "/fi/hakemus/kasvatus_ja_koulutus_toiminta_av/",
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
        'edit-subventions-items-0-amount',
        'edit-compensation-purpose',
        'edit-compensation-explanation',
      ],
    },
    '3_yhteison_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-community-practices-business-1',
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
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-compensation-purpose': 'Virhe sivulla 2. Avustustiedot: Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista kenttä on pakollinen.',
    'edit-compensation-explanation': 'Virhe sivulla 2. Avustustiedot: Selvitys avustuksen käytöstä kenttä on pakollinen.',
    'edit-community-practices-business-1': 'Virhe sivulla 3. Yhteisön toiminta: Harjoittaako yhteisö liiketoimintaa kenttä on pakollinen.',
  },
};

const wrongValues: FormDataWithRemoveOptionalProps = {
  title: 'Wrong values',
  formPages: {
    '1_hakijan_tiedot': {
      items: {
        "edit-email": {
          role: 'input',
          value: 'ääkkösiä@vaa',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-email',
          }
        },
      },
      itemsToRemove: [],
    },
    '3_yhteison_tiedot': {
      items: {
        "edit-toimintapaikka-items-0-item-postcode": {
          role: 'input',
          value: '3543',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-toimintapaikka-items-0-item-postcode',
          }
        },
      },
      itemsToRemove: [],
    },
    '4_talous': {
      items: {},
      itemsToRemove: [
        'edit-muut-avustukset-field-items-0-item-value',
        'edit-muut-menot-4-items-0-item-label',
        'edit-muut-palveluiden-ostot-2-items-0-item-value',
        'edit-muut-menot-tarvikkeet-items-0-item-label',
        'edit-muut-menot-2-items-0-item-value',
        'edit-muut-menot-3-items-0-item-label',
      ],
    },
    'webform_preview': {
      items: {},
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite kenttä ei ole oikeassa muodossa.',
    'edit-toimintapaikka-items-0-item-postcode': 'Virhe sivulla 3. Yhteisön toiminta: Käytä muotoa FI-XXXXX tai syötä postinumero viisinumeroisena.',
    'edit-muut-avustukset-field-items-0-item-value': 'Virhe sivulla 4. Talous: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon',
    'edit-muut-menot-4-items-0-item-label': 'Virhe sivulla 4. Talous: Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
    'edit-muut-palveluiden-ostot-2-items-0-item-value': 'Virhe sivulla 4. Talous: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon',
    'edit-muut-menot-tarvikkeet-items-0-item-label': 'Virhe sivulla 4. Talous: Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
    'edit-muut-menot-2-items-0-item-value': 'Virhe sivulla 4. Talous: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon',
    'edit-muut-menot-3-items-0-item-label': 'Virhe sivulla 4. Talous: Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
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

const registeredCommunityApplications_52 = {
  draft: baseFormRegisteredCommunity_52,
  missing_values: createFormData(baseFormRegisteredCommunity_52, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_52, wrongValues),
  // success: createFormData(baseFormRegisteredCommunity_52, sendApplication),
}

export {
  registeredCommunityApplications_52
}

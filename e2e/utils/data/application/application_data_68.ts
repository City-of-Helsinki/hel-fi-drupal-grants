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
import {
  viewPageFormatAddress,
  viewPageFormatBoolean, viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatCurrency, viewPageFormatNumber
} from "../../view_page_formatters";
import {PROFILE_INPUT_DATA} from "../profile_input_data";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_68: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-hyte-yleisavustus-form',
  formPath: '/fi/form/hyte-yleisavustus',
  formPages: {
    "1_hakijan_tiedot": {
      items: {
        "edit-email": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-email',
          },
          value: faker.internet.email(),
          viewPageFormatter: viewPageFormatLowerCase,
        },
        "edit-contact-person": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-contact-person',
          },
          value: faker.person.fullName(),
        },
        "edit-contact-person-phone-number": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-contact-person-phone-number',
          },
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
        "edit-compensation-purpose": {
          value: faker.lorem.sentences(4),
        },
        "edit-myonnetty-avustus": {
          role: 'dynamicmultivalue',
          label: '',
          dynamic_multi: {
            radioSelector: {
              type: 'dom-id-label',
              name: 'data-drupal-selector',
              value: 'edit-olemme-saaneet-muita-avustuksia-1',
            },
            revealedElementSelector: {
              type: 'dom-id',
              name: '',
              value: '#edit-myonnetty-avustus',
            },
            multi: {
              buttonSelector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-myonnetty-avustus-add-submit',
                resultValue: 'edit-myonnetty-avustus-items-[INDEX]',
              },
              //@ts-ignore
              items: {
                0: [
                  {
                    role: 'select',
                    selector: {
                      type: 'by-label',
                      name: '',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer',
                    },
                    value: 'Valtio',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer-name',
                    },
                    value: faker.lorem.words(2).toUpperCase(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-year',
                    },
                    value: faker.date.past().getFullYear().toString(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector-sequential',
                      name: 'data-drupal-selector-sequential',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                    },
                    value: faker.number.float({
                      min: 1000,
                      max: 10000,
                      precision: 2
                    }).toString(),
                    viewPageFormatter: viewPageFormatCurrency,
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-purpose',
                    },
                    value: faker.lorem.words(30),
                  },
                ],
                1: [
                  {
                    role: 'select',
                    selector: {
                      type: 'by-label',
                      name: '',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer',
                    },
                    value: 'EU',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer-name',
                    },
                    value: faker.lorem.words(2).toUpperCase(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-year',
                    },
                    value: faker.date.past().getFullYear().toString(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector-sequential',
                      name: 'data-drupal-selector-sequential',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                    },
                    value: faker.number.float({
                      min: 1000,
                      max: 10000,
                      precision: 2
                    }).toString(),
                    viewPageFormatter: viewPageFormatCurrency,
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-purpose',
                    },
                    value: faker.lorem.words(30),
                  },
                ],
              },
              expectedErrors: {}
            }
          },
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
          },
          viewPageSkipValidation: true,
        },
      },
    },
    '3_yhteison_tiedot': {
      items: {
        "edit-business-purpose": {
          value: faker.lorem.sentences(4),
        },
        "edit-community-practices-business-1": {
          value: "Ei",
          viewPageFormatter: viewPageFormatBoolean,
        },
        "edit-fee-person": {
          value: faker.number.float({
            min: 100,
            max: 1000,
            precision: 2
          }).toString(),
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-fee-community": {
          value: faker.number.float({
            min: 100,
            max: 1000,
            precision: 2
          }).toString(),
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
          }
        },
      }
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
          viewPageFormatter: viewPageFormatFilePath
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
          viewPageFormatter: viewPageFormatFilePath
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
          viewPageFormatter: viewPageFormatFilePath
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
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-vuosikokouksen-poytakirja-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vuosikokouksen_poytakirja_attachment]"]',
            resultValue: '.form-item-vuosikokouksen-poytakirja-attachment a',
          },
          value: PATH_VUOSIKOKOUKSEN_POYTAKIRJA,
          viewPageFormatter: viewPageFormatFilePath
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
          viewPageFormatter: viewPageFormatFilePath
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
          viewPageFormatter: viewPageFormatFilePath
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
  expectedDestination: "/fi/hakemus/hyte_yleisavustus/",
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
        'edit-community-address-community-address-select'
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
    'lisatiedot_ja_liitteet': {
      items: {},
      itemsToRemove: [
        'edit-yhteison-saannot-attachment-upload',
        'edit-vahvistettu-tilinpaatos-attachment-upload',
        'edit-vahvistettu-toimintakertomus-attachment-upload',
        'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload',
        'edit-vuosikokouksen-poytakirja-attachment-upload',
        'edit-toimintasuunnitelma-attachment-upload',
        'edit-talousarvio-attachment-upload',
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
    'edit-community-practices-business-0': 'Virhe sivulla 3. Yhteisön toiminta: Harjoittaako yhteisö liiketoimintaa kenttä on pakollinen.',
    'edit-yhteison-saannot-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Yhteisön säännöt ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vahvistettu-tilinpaatos-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Vahvistettu tilinpäätös (edelliseltä päättyneeltä tilikaudelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vahvistettu-toimintakertomus-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Vahvistettu toimintakertomus (edelliseltä päättyneeltä tilikaudelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Vahvistettu tilin- tai toiminnantarkastuskertomus (edelliseltä päättyneeltä tilikaudelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vuosikokouksen-poytakirja-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Vuosikokouksen pöytäkirja, jossa on vahvistettu edellisen päättyneen tilikauden tilinpäätös ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-toimintasuunnitelma-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Toimintasuunnitelma (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-talousarvio-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Talousarvio (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
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
    'webform_preview': {
      items: {},
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
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
          }
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {},
};

const registeredCommunityApplications_68 = {
  draft: baseFormRegisteredCommunity_68,
  missing_values: createFormData(baseFormRegisteredCommunity_68, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_68, wrongValues),
  success: createFormData(baseFormRegisteredCommunity_68, sendApplication),
}

export {
  registeredCommunityApplications_68
}


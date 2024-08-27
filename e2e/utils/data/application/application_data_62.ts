import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PROFILE_INPUT_DATA} from "../profile_input_data";
import {ATTACHMENTS} from "../attachment_data";
import {createFormData} from "../../form_data_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatCurrency,
  viewPageFormatNumber,
  viewPageFormatDate
} from "../../view_page_formatters";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_62: FormData = {
  title: 'Save as draft.',
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

        "edit-haettu-avustus-tieto": {
          role: 'dynamicmultivalue',
          label: '',
          dynamic_multi: {
            radioSelector: {
              type: 'dom-id-label',
              name: 'data-drupal-selector',
              value: 'edit-olemme-hakeneet-avustuksia-muualta-kuin-helsingin-kaupungilta-1',
            },
            revealedElementSelector: {
              type: 'dom-id',
              name: '',
              value: '#edit-haettu-avustus-tieto',
            },
            multi: {
              buttonSelector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-haettu-avustus-tieto-add-submit',
                resultValue: 'edit-haettu-avustus-tieto-items-[INDEX]',
              },
              //@ts-ignore
              items: {
                0: [
                  {
                    role: 'select',
                    selector: {
                      type: 'by-label',
                      name: '',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-issuer',
                    },
                    value: 'Muu',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-issuer-name',
                    },
                    value: faker.lorem.words(2).toUpperCase(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-year',
                    },
                    value: faker.date.past().getFullYear().toString(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector-sequential',
                      name: 'data-drupal-selector-sequential',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-amount',
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
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-purpose',
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
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-issuer',
                    },
                    value: 'Säätiö',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-issuer-name',
                    },
                    value: faker.lorem.words(2).toUpperCase(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-year',
                    },
                    value: faker.date.past().getFullYear().toString(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector-sequential',
                      name: 'data-drupal-selector-sequential',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-amount',
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
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-purpose',
                    },
                    value: faker.lorem.words(30),
                  },
                ],
              },
              expectedErrors: {}
            }
          },
        },
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
          viewPageSelector: '#nuorisotoiminta_projektiavustush--projektin_aikataulu',
        },
        "edit-projekti-loppuu": {
          value: '2023-12-31',
          viewPageFormatter: viewPageFormatDate,
          viewPageSelector: '#nuorisotoiminta_projektiavustush--projektin_aikataulu',
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
        'edit-budget-other-income': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-budget-other-income-add-submit',
              resultValue: 'edit-budget-other-income-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-budget-other-income-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-budget-other-income',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-budget-other-income-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-budget-other-income',
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-budget-other-income-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-budget-other-income',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-budget-other-income-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-budget-other-income',
                },
              ],
            },
            expectedErrors: {}
          },
        },
        'edit-budget-other-cost': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-budget-other-cost-add-submit',
              resultValue: 'edit-budget-other-cost-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-budget-other-cost-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-budget-other-cost',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-budget-other-cost-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-budget-other-cost',
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-budget-other-cost-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-budget-other-cost',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-budget-other-cost-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-budget-other-cost',
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
          value: ATTACHMENTS.YHTEISON_SAANNOT,
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
          value: ATTACHMENTS.TOIMINTASUUNNITELMA,
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
          value: ATTACHMENTS.TALOUSARVIO,
          viewPageFormatter: viewPageFormatFilePath,
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
 * Basic form data.
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
      itemsToRemove: [
        'edit-projektin-nimi',
        'edit-projekti-alkaa',
        'edit-projekti-loppuu',
        'edit-osallistujat-7-28',
        'edit-osallistujat-kaikki'
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
  },
};

const missingValuesUnregistered: FormDataWithRemoveOptionalProps = {
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
      itemsToRemove: [
        'edit-projektin-nimi',
        'edit-projekti-alkaa',
        'edit-projekti-loppuu',
        'edit-osallistujat-7-28',
        'edit-osallistujat-kaikki'
      ],
    },
    'webform_preview': {
      items: {},
      itemsToRemove: [],
    },
  },
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
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
      expectedInlineErrors: [
        { selector: '.webform-type-grants-budget-other-income', errorMessage: 'Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon' },
        { selector: '.webform-type-grants-budget-other-cost', errorMessage: 'Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon' },
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
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: ääkkösiävaa ei ole kelvollinen sähköpostiosoite. Täytä sähköpostiosoite muodossa user@example.com.',
    'edit-budget-other-income-items-0-item-value': 'Virhe sivulla 5. Talousarvio: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon',
    'edit-budget-other-cost-items-0-item-label': 'Virhe sivulla 5. Talousarvio: Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
  },
};

const wrongValuesUnregistered: FormDataWithRemoveOptionalProps = {
  title: 'Wrong values',
  viewPageSkipValidation: true,
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
      expectedInlineErrors: [
        { selector: '.webform-type-grants-budget-other-income', errorMessage: 'Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon' },
        { selector: '.webform-type-grants-budget-other-cost', errorMessage: 'Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon' },
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
          },
          viewPageSkipValidation: true
        },
      },
      itemsToRemove: [],
    },
  },
  expectedErrors: {},
};

const registeredCommunityApplications_62 = {
  draft: baseFormRegisteredCommunity_62,
  missing_values: createFormData(baseFormRegisteredCommunity_62, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_62, wrongValues),
  success: createFormData(baseFormRegisteredCommunity_62, sendApplication),
}

/**
 * All data for unregistered community applications.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 */
const unRegisteredCommunityApplications_62 = {
  draft: baseFormUnRegisteredCommunity_62,
  missing_values: createFormData(baseFormUnRegisteredCommunity_62, missingValuesUnregistered),
  wrong_values: createFormData(baseFormUnRegisteredCommunity_62, wrongValuesUnregistered),
  success: createFormData(baseFormUnRegisteredCommunity_62, sendApplication),
}

export {
  registeredCommunityApplications_62,
  unRegisteredCommunityApplications_62
}

import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker";
import {PROFILE_INPUT_DATA} from "../profile_input_data";
import {ATTACHMENTS} from "../attachment_data";
import {createFormData} from "../../form_data_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatDate,
  viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatCurrency,
  viewPageFormatNumber,
} from "../../view_page_formatters";
import {getFakeEmailAddress} from "../../field_helpers";


/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_52: FormData = {
  title: 'Save as draft.',
  formSelector: 'webform-submission-kasvatus-ja-koulutus-toiminta-av-form',
  formPath: '/fi/form/kasvatus-ja-koulutus-toiminta-av',
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
                      multipleOf: 2
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
                      multipleOf: 2
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
        "edit-community-practices-business-0": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-community-practices-business-0',
          },
          value: "Ei",
        },
        'edit-toimintapaikka': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-toimintapaikka-add-submit',
              resultValue: 'edit-toimintapaikka-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-location',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-streetaddress',
                  },
                  value: faker.location.streetAddress(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-postcode',
                  },
                  value: faker.location.zipCode(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-studentcount',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-specialstudents',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-groupcount',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-specialgroups',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-personnelcount',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber
                },
                {
                  role: 'radio',
                  selector: {
                    type: 'partial-for-attribute',
                    name: '',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-free-no',
                  },
                  value: "Ei",
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-totalrent',
                  },
                  value: faker.number.float({
                    min: 100,
                    max: 1000,
                    multipleOf: 2
                  }).toString(),
                  viewPageFormatter: viewPageFormatCurrency
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-renttimebegin',
                  },
                  value: '2023-12-11',
                  viewPageFormatter: viewPageFormatDate
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-renttimeend',
                  },
                  value: '2023-12-31',
                  viewPageFormatter: viewPageFormatDate
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-location',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-streetaddress',
                  },
                  value: faker.location.streetAddress(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-postcode',
                  },
                  value: faker.location.zipCode(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-studentcount',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-specialstudents',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-groupcount',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-specialgroups',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-personnelcount',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber
                },
                {
                  role: 'radio',
                  selector: {
                    type: 'partial-for-attribute',
                    name: '',
                    value: 'edit-toimintapaikka-items-[INDEX]-item-free-yes',
                  },
                  value: "Kyllä",
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
            value: '4_talous',
          },
          viewPageSkipValidation: true,
        },
      }
    },
    "4_talous": {
      items: {
        "edit-tulot-customerfees": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
          viewPageSelector: '.form-item-tulot',
        },
        "edit-tulot-donations": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-tulot',
          viewPageFormatter: viewPageFormatNumber
        },
        'edit-muut-avustukset': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-muut-avustukset-field-add-submit',
              resultValue: 'edit-muut-avustukset-field-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-avustukset-field-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-avustukset-field',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-avustukset-field-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-avustukset-field',
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-avustukset-field-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-avustukset-field',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-avustukset-field-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-avustukset-field',
                },
              ],
            },
            expectedErrors: {}
          },
        },
        "edit-henkilostomenot-ja-vuokrat-salaries": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-henkilostomenot-ja-vuokrat',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-henkilostomenot-ja-vuokrat-personnelsidecosts": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-henkilostomenot-ja-vuokrat',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-henkilostomenot-ja-vuokrat-rentsum": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-henkilostomenot-ja-vuokrat',
          viewPageFormatter: viewPageFormatNumber
        },
        'edit-muut-menot': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-muut-menot-4-add-submit',
              resultValue: 'edit-muut-menot-4-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-menot-4-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-menot-4',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-menot-4-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-menot-4',
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-menot-4-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-menot-4',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-menot-4-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-menot-4',
                },
              ],
            },
            expectedErrors: {}
          },
        },
        "edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-snacks": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-cleaning": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-premisesservice": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2-travelcosts": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-avustuksen-kaytto-palveluiden-ostot-eriteltyina-2',
          viewPageFormatter: viewPageFormatNumber
        },
        'edit-muut-palveluiden-ostot-2': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-muut-palveluiden-ostot-2-add-submit',
              resultValue: 'edit-muut-palveluiden-ostot-2-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-palveluiden-ostot-2-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-palveluiden-ostot-2',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-palveluiden-ostot-2-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-palveluiden-ostot-2',
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-palveluiden-ostot-2-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-palveluiden-ostot-2',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-palveluiden-ostot-2-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-palveluiden-ostot-2',
                },
              ],
            },
            expectedErrors: {}
          },
        },
        "edit-muut-aineet-tarvikkeet-ja-tavarat-2-snacks": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-muut-aineet-tarvikkeet-ja-tavarat-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-muut-aineet-tarvikkeet-ja-tavarat-2-heating": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-muut-aineet-tarvikkeet-ja-tavarat-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-muut-aineet-tarvikkeet-ja-tavarat-2-water": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-muut-aineet-tarvikkeet-ja-tavarat-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-muut-aineet-tarvikkeet-ja-tavarat-2-electricity": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-muut-aineet-tarvikkeet-ja-tavarat-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-muut-aineet-tarvikkeet-ja-tavarat-2-supplies": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-muut-aineet-tarvikkeet-ja-tavarat-2',
          viewPageFormatter: viewPageFormatNumber
        },
        'edit-muut-menot-tarvikkeet': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-muut-menot-tarvikkeet-add-submit',
              resultValue: 'edit-muut-menot-tarvikkeet-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-menot-tarvikkeet-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-menot-tarvikkeet',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-menot-tarvikkeet-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-menot-tarvikkeet',
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-menot-tarvikkeet-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-menot-tarvikkeet',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-menot-tarvikkeet-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-menot-tarvikkeet',
                },
              ],
            },
            expectedErrors: {}
          },
        },
        "edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-admin": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-avustuksen-kaytto-muut-kulut-eriteltyina-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-accounting": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-avustuksen-kaytto-muut-kulut-eriteltyina-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-avustuksen-kaytto-muut-kulut-eriteltyina-2-health": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-avustuksen-kaytto-muut-kulut-eriteltyina-2',
          viewPageFormatter: viewPageFormatNumber
        },
        'edit-muut-menot-2': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-muut-menot-2-add-submit',
              resultValue: 'edit-muut-menot-2-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-menot-2-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-menot-2',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-menot-2-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-menot-2',
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-menot-2-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-menot-2',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-menot-2-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-menot-2',
                },
              ],
            },
            expectedErrors: {}
          },
        },
        "edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-salaries": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-personnelsidecosts": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-rentsum": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-materials": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2-services": {
          role: 'number-input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-asiakasmaksutulojen-kaytto-ja-mahdolliset-lahjoitukset-2',
          viewPageFormatter: viewPageFormatNumber
        },
        'edit-muut-kulut-2': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-muut-menot-3-add-submit',
              resultValue: 'edit-muut-menot-3-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-menot-3-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-menot-3',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-menot-3-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-menot-3',
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muut-menot-3-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-muut-menot-3',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-muut-menot-3-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-muut-menot-3',
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
        'edit-vahvistettu-tilinpaatos-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilinpaatos_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilinpaatos-attachment a',
          },
          value: ATTACHMENTS.VAHVISTETTU_TILINPAATOS,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-vahvistettu-toimintakertomus-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_toimintakertomus_attachment]"]',
            resultValue: '.form-item-vahvistettu-toimintakertomus-attachment a',
          },
          value: ATTACHMENTS.VAHVISTETTU_TOIMINTAKERTOMUS,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilin_tai_toiminnantarkastuskertomus_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment a',
          },
          value: ATTACHMENTS.VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-toimintasuunnitelma-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[toimintasuunnitelma_attachment]"]',
            resultValue: '.form-item-toimintasuunnitelma-attachment a',
          },
          value: ATTACHMENTS.TOIMINTASUUNNITELMA,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-vuokrasopimus-haettaessa-vuokra-avustusta-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vuokrasopimus_haettaessa_vuokra_avustusta__attachment]"]',
            resultValue: '.form-item-vuokrasopimus-haettaessa-vuokra-avustusta--attachment a',
          },
          value: ATTACHMENTS.MUU_LIITE,
          viewPageSelector: '.form-item-vuokrasopimus-haettaessa-vuokra-avustusta-',
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-talousarvio-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[talousarvio_attachment]"]',
            resultValue: '.form-item-talousarvio-attachment a',
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
  expectedDestination: "/fi/hakemus/kasvatus_ja_koulutus_toiminta_av/",
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
      expectedInlineErrors: [
        { selector: '.form-item-bank-account-account-number-select', errorMessage: 'Valitse tilinumero kenttä on pakollinen.' },
        { selector: '.form-item-email', errorMessage: 'Sähköpostiosoite kenttä on pakollinen.' },
        { selector: '.form-item-contact-person', errorMessage: 'Yhteyshenkilö kenttä on pakollinen.' },
        { selector: '.form-item-contact-person-phone-number', errorMessage: 'Puhelinnumero kenttä on pakollinen.' },
        { selector: '.webform-type-community-address-composite', errorMessage: 'Yhteisön osoite kenttä on pakollinen.' },
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
        'edit-community-practices-business-0',
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
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-compensation-purpose': 'Virhe sivulla 2. Avustustiedot: Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista kenttä on pakollinen.',
    'edit-compensation-explanation': 'Virhe sivulla 2. Avustustiedot: Selvitys avustuksen käytöstä kenttä on pakollinen.',
    'edit-community-practices-business-0': 'Virhe sivulla 3. Yhteisön toiminta: Harjoittaako yhteisö liiketoimintaa kenttä on pakollinen.',
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
          value: 'ääkkösiä@vaa',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-email',
          },
        },
      },
      itemsToRemove: [],
      expectedInlineErrors: [
        { selector: '.form-item-email', errorMessage: 'Sähköpostiosoite kenttä ei ole oikeassa muodossa.' },
      ],
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
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite kenttä ei ole oikeassa muodossa.',
    'edit-muut-avustukset-field-items-0-item-value': 'Virhe sivulla 4. Talous: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon',
    'edit-muut-menot-4-items-0-item-label': 'Virhe sivulla 4. Talous: Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
    'edit-muut-palveluiden-ostot-2-items-0-item-value': 'Virhe sivulla 4. Talous: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon',
    'edit-muut-menot-tarvikkeet-items-0-item-label': 'Virhe sivulla 4. Talous: Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
    'edit-muut-menot-2-items-0-item-value': 'Virhe sivulla 4. Talous: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon',
    'edit-muut-menot-3-items-0-item-label': 'Virhe sivulla 4. Talous: Kuvaus menosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
  },
};

const fieldSwapForm: FormDataWithRemoveOptionalProps = {
  title: 'Field swap form',
  testFieldSwap: true,
  formPages: {
    "1_hakijan_tiedot": {
      items: {
        'edit-bank-account-account-number-select': {
          role: 'select',
          value: PROFILE_INPUT_DATA.iban,
          viewPageSelector: '.form-item-bank-account',
        },
      },
      itemsToSwap: [
        {field: 'edit-bank-account-account-number-select', swapValue: PROFILE_INPUT_DATA.iban2},
      ]
    },
    "2_avustustiedot": {
      items: {},
      itemsToSwap: [
        {field: 'edit-compensation-purpose', swapValue: 'The new purpose!'},
      ]
    },
    "lisatiedot_ja_liitteet": {
      items: {
        'edit-yhteison-saannot-attachment-upload': {
          role: 'checkbox',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-yhteison-saannot-isdeliveredlater'
          },
          value: 'Liite toimitetaan myöhemmin',
        },
        'edit-vahvistettu-tilinpaatos-attachment-upload': {
          role: 'checkbox',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-vahvistettu-tilinpaatos-isincludedinotherfile'
          },
          value: 'Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä',
        },
      },
    },
  },
  expectedErrors: {},
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

const registeredCommunityApplications_52 = {
  draft: baseFormRegisteredCommunity_52,
  missing_values: createFormData(baseFormRegisteredCommunity_52, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_52, wrongValues),
  swap_fields: createFormData(baseFormRegisteredCommunity_52, fieldSwapForm),
  success: createFormData(baseFormRegisteredCommunity_52, sendApplication),
}

export {
  registeredCommunityApplications_52
}

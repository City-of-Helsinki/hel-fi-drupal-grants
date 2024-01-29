import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PATH_MUU_LIITE} from "../../helpers";
import {createFormData} from "../../form_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatBoolean, viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatCurrency
} from "../../view_page_formatters";
import {PROFILE_INPUT_DATA} from "../profile_input_data";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_51: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-kasvatus-ja-koulutus-yleisavustu-form',
  formPath: '/fi/form/kasvatus-ja-koulutus-yleisavustu',
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
        "edit-compensation-purpose": {
          value: faker.lorem.sentences(4),
        },
        "edit-benefits-loans": {
          value: faker.lorem.sentences(4),
        },
        "edit-benefits-premises": {
          value: faker.lorem.sentences(4),
        },
        "olemme-saaneet-muita-avustuksia": {
          role: 'dynamicmultifield',
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
            multi_field: {
              buttonSelector: {
                type: 'add-more-button',
                name: 'data-drupal-selector',
                value: 'Lisää uusi myönnetty avustus',
                resultValue: 'edit-myonnetty-avustus-items-[INDEX]',
              },
              // @ts-ignore
              items: {
                0: [
                  {
                    role: 'select',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'name',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer'
                    },
                    value: '3',
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
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                    },
                    value: faker.finance.amount({
                      min: 100,
                      max: 10000,
                      autoFormat: true
                    }),
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
                // 1: [
                //   {
                //     role: 'select',
                //     selector: {
                //       type: 'data-drupal-selector',
                //       name: 'name',
                //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer',
                //     },
                //     value: 'use-random-value',
                //   },
                //   {
                //     role: 'input',
                //     selector: {
                //       type: 'data-drupal-selector',
                //       name: 'data-drupal-selector',
                //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer-name',
                //     },
                //     value: faker.lorem.words(2).toUpperCase(),
                //   },
                //   {
                //     role: 'input',
                //     selector: {
                //       type: 'data-drupal-selector',
                //       name: 'data-drupal-selector',
                //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-year',
                //     },
                //     value: faker.date.past().getFullYear().toString(),
                //   },
                //   {
                //     role: 'input',
                //     selector: {
                //       type: 'data-drupal-selector',
                //       name: 'data-drupal-selector',
                //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                //     },
                //     value: faker.finance.amount({
                //       min: 100,
                //       max: 10000,
                //       autoFormat: true
                //     }),
                //   },
                //   {
                //     role: 'input',
                //     selector: {
                //       type: 'data-drupal-selector',
                //       name: 'data-drupal-selector',
                //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-purpose',
                //     },
                //     value: faker.lorem.words(30),
                //   },
                // ],
              },
              expectedErrors: {}
            }
          },
          viewPageSkipValidation: true,
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
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-community-practices-business-1',
          },
          value: "0",
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
        },
        "edit-members-applicant-person-local": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-members-applicant-community-global": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-members-applicant-community-local": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
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
      }
    },
    "lisatiedot_ja_liitteet": {
      items: {
        "edit-additional-information": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-yhteison-saannot-isdeliveredlater": {
          role: 'checkbox',
          value: "1",
          viewPageSkipValidation: true,
        },
        "edit-vahvistettu-tilinpaatos-isdeliveredlater": {
          role: 'checkbox',
          value: "1",
          viewPageSkipValidation: true,
        },
        "edit-vahvistettu-toimintakertomus-isdeliveredlater": {
          role: 'checkbox',
          value: "1",
          viewPageSkipValidation: true,
        },
        "edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-isdeliveredlater": {
          role: 'checkbox',
          value: "1",
          viewPageSkipValidation: true,
        },
        "edit-vuosikokouksen-poytakirja-isdeliveredlater": {
          role: 'checkbox',
          value: "1",
          viewPageSkipValidation: true,
        },
        "edit-toimintasuunnitelma-isdeliveredlater": {
          role: 'checkbox',
          value: "1",
          viewPageSkipValidation: true,
        },
        "edit-talousarvio-isdeliveredlater": {
          role: 'checkbox',
          value: "1",
          viewPageSkipValidation: true,
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
          viewPageSkipValidation: true
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
          value: 'submit-form',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-submit',
          },
          viewPageSkipValidation: true,
        },
      },
    },
  },
  expectedErrors: {},
  expectedDestination: "/fi/hakemus/kasvatus_ja_koulutus_yleisavustu/",
}

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values from 1st page',
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: ['edit-bank-account-account-number-select'],
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
          viewPageSkipValidation: true,
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
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
          },
          viewPageSkipValidation: true,
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {},
};


const registeredCommunityApplications_51 = {
  success: baseFormRegisteredCommunity_51,
  draft: createFormData(baseFormRegisteredCommunity_51, saveDraft),
}

export {
  registeredCommunityApplications_51
}

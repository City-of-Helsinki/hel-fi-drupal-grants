import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PATH_TO_TEST_PDF} from "../../helpers";
import {createFormData} from "../../form_helpers";

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
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-email',
          },
          value: faker.internet.email(),
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
          selector: {
            type: 'dom-id-first',
            name: 'bank-account-selector',
            value: '#edit-bank-account-account-number-select',
          },
          value: '',
        },
        "edit-community-address-community-address-select": {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: 'bank-account-selector',
            value: '#edit-community-address-community-address-select',
          },
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
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-wizard-next',
          }
        },
      },
    },
    "2_avustustiedot": {
      items: {
        "acting_year": {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: 'bank-account-selector',
            value: '#edit-acting-year',
          },
          value: '',
        },
        "subvention_amount": {
          value: '5709,98',
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
        // "olemme-saaneet-muita-avustuksia": {
        //   role: 'dynamicmultifield',
        //   label: '',
        //   dynamic_multi: {
        //     radioSelector: {
        //       type: 'dom-id-label',
        //       name: 'data-drupal-selector',
        //       value: 'edit-olemme-saaneet-muita-avustuksia-1',
        //     },
        //     revealedElementSelector: {
        //       type: 'dom-id',
        //       name: '',
        //       value: '#edit-myonnetty-avustus',
        //     },
        //     multi_field: {
        //       buttonSelector: {
        //         type: 'add-more-button',
        //         name: 'data-drupal-selector',
        //         value: 'Lisää uusi myönnetty avustus',
        //         resultValue: 'edit-myonnetty-avustus-items-[INDEX]',
        //       },
        //       // @ts-ignore
        //       items: {
        //         0: [
        //           {
        //             role: 'select',
        //             selector: {
        //               type: 'data-drupal-selector',
        //               name: 'name',
        //               value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer'
        //             },
        //             value: '3',
        //           },
        //           {
        //             role: 'input',
        //             selector: {
        //               type: 'data-drupal-selector',
        //               name: 'data-drupal-selector',
        //               value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer-name',
        //             },
        //             value: faker.lorem.words(2).toUpperCase(),
        //           },
        //           {
        //             role: 'input',
        //             selector: {
        //               type: 'data-drupal-selector',
        //               name: 'data-drupal-selector',
        //               value: 'edit-myonnetty-avustus-items-[INDEX]-item-year',
        //             },
        //             value: faker.date.past().getFullYear().toString(),
        //           },
        //           {
        //             role: 'input',
        //             selector: {
        //               type: 'data-drupal-selector',
        //               name: 'data-drupal-selector',
        //               value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
        //             },
        //             value: faker.finance.amount({
        //               min: 100,
        //               max: 10000,
        //               autoFormat: true
        //             }),
        //           },
        //           {
        //             role: 'input',
        //             selector: {
        //               type: 'data-drupal-selector',
        //               name: 'data-drupal-selector',
        //               value: 'edit-myonnetty-avustus-items-[INDEX]-item-purpose',
        //             },
        //             value: faker.lorem.words(30),
        //           },
        //         ],
        //         // 1: [
        //         //   {
        //         //     role: 'select',
        //         //     selector: {
        //         //       type: 'data-drupal-selector',
        //         //       name: 'name',
        //         //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer',
        //         //     },
        //         //     value: 'use-random-value',
        //         //   },
        //         //   {
        //         //     role: 'input',
        //         //     selector: {
        //         //       type: 'data-drupal-selector',
        //         //       name: 'data-drupal-selector',
        //         //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer-name',
        //         //     },
        //         //     value: faker.lorem.words(2).toUpperCase(),
        //         //   },
        //         //   {
        //         //     role: 'input',
        //         //     selector: {
        //         //       type: 'data-drupal-selector',
        //         //       name: 'data-drupal-selector',
        //         //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-year',
        //         //     },
        //         //     value: faker.date.past().getFullYear().toString(),
        //         //   },
        //         //   {
        //         //     role: 'input',
        //         //     selector: {
        //         //       type: 'data-drupal-selector',
        //         //       name: 'data-drupal-selector',
        //         //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
        //         //     },
        //         //     value: faker.finance.amount({
        //         //       min: 100,
        //         //       max: 10000,
        //         //       autoFormat: true
        //         //     }),
        //         //   },
        //         //   {
        //         //     role: 'input',
        //         //     selector: {
        //         //       type: 'data-drupal-selector',
        //         //       name: 'data-drupal-selector',
        //         //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-purpose',
        //         //     },
        //         //     value: faker.lorem.words(30),
        //         //   },
        //         // ],
        //       },
        //       expectedErrors: {}
        //     }
        //   },
        // },
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
        "edit-community-practices-business-1": {
          value: '',
        },
        "edit-fee-person": {
          value: '321,12',
        },
        "edit-fee-community": {
          value: '321,12',
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
          }
        },
      }
    },
    "lisatiedot_ja_liitteet": {
      items: {
        "edit-additional-information": {
          role: 'input',
          selector: {
            type: 'role',
            name: 'Role',
            details: {
              role: 'textbox',
              options: {
                name: 'Lisätiedot'
              }
            },
          },
          value: faker.lorem.sentences(3),
        },
        'muu_liite_0': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[muu_liite_items_0__item__attachment]"]',
            resultValue: '.form-item-muu-liite-items-0--item--attachment a',
          },
          value: PATH_TO_TEST_PDF,
        },
        'muu_liite_0_kuvaus': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-muu-liite-items-0-item-description',
          },
          value: faker.lorem.sentences(1),
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
        },
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
      // itemsToRemove: ['edit-bank-account-account-number-select'],
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
          }
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    // 'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
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
          }
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

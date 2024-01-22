import {
  FormData,
  FormDataWithRemoveOptionalProps,
  FormPage
} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PATH_TO_TEST_PDF} from "../../helpers";
import {PATH_MUU_LIITE, PATH_VAHVISTETTU_TILINPAATOS} from "../../helpers";
import {PROFILE_INPUT_DATA} from "../profile_input_data";
import {createFormData} from "../../form_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatBoolean, viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatNumber
} from "../../view_page_formatters";


const formPath = '/fi/hakemus/kaupunginhallituksen-yleisavustushakemus';
const formSelector = 'webform-submission-yleisavustushakemus-form';


const baseForm_29: FormData = {
  title: 'Form submit',
  formSelector: formSelector,
  formPath: formPath,
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
          value: '2024',
        },
        "edit-subventions-items-0-amount": {
          value: '5709,98',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatNumber
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
          },
          viewPageSkipValidation: true
        },
      },
    },
    '3_yhteison_tiedot': {
      items: {
        "edit-business-purpose": {
          value: faker.lorem.sentences(4),
        },
        "edit-community-practices-business-1": {
          value: "0",
          viewPageFormatter: viewPageFormatBoolean
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
          viewPageSelector: '.form-item-muu-liite'
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
          viewPageSelector: '.form-item-vahvistettu-tilinpaatos',
          viewPageFormatter: viewPageFormatFilePath
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
          viewPageSkipValidation: true
        },
      },
    },
    "webform_preview": {
      items: {
        "accept_terms_1": {
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
  expectedDestination: "/fi/hakemus/yleisavustushakemus/",
}


/**
 * Basic form data for successful submit to Avus2
 */
const baseFormPrivatePerson_29: FormData = createFormData(
  baseForm_29,
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


const baseFormUnRegisteredCommunity_29: FormData = createFormData(
  baseForm_29,
  {
    title: 'Form submit',
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
)

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
          viewPageSkipValidation: true
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

const saveAvus2: FormDataWithRemoveOptionalProps = {
  title: 'Safe to draft and verify data',
  formPages: {
    "webform_preview": {
      items: {
        "sendbutton": {
          role: 'button',
          value: 'save-submit',
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
  expectedDestination: '',
  expectedErrors: {},
};

/**
 * Overridden form to save as a DRAFT
 */
const saveDraft: FormDataWithRemoveOptionalProps = {
  title: 'Save to draft and verify data',
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

const privatePersonApplications_29 = {
  // success: baseFormPrivatePerson_29,
  draft: createFormData(baseFormPrivatePerson_29, saveDraft),
  // missing_values: createFormData(baseFormPrivatePerson_29, missingValues),
}

const registeredCommunityApplications_29 = {
  // success: baseForm_29,
  draft: createFormData(baseForm_29, saveDraft),
}
const unRegisteredCommunityApplications_29 = {
  // success: baseFormUnRegisteredCommunity_29,
  draft: createFormData(baseFormUnRegisteredCommunity_29, saveDraft),
}

export {
  privatePersonApplications_29,
  registeredCommunityApplications_29,
  unRegisteredCommunityApplications_29
}

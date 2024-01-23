import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PATH_TO_TEST_PDF, PATH_MUU_LIITE} from "../../helpers";
import {createFormData} from "../../form_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatBoolean, viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatNumber
} from "../../view_page_formatters";
import {PROFILE_INPUT_DATA} from "../profile_input_data";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_56: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-liikunta-yleisavustushakemus-form',
  formPath: '/fi/form/liikunta-yleisavustushakemus',
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
        "compensation-no": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'compensation-no',
          },
          value: "0",
          viewPageSkipValidation: true
        },
        "edit-subventions-items-0-amount": {
          value: '5709,98',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-compensation-purpose": {
          value: faker.lorem.sentences(4),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'lisatiedot_ja_liitteet',
          },
          viewPageSkipValidation: true
        },
      },
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
          viewPageFormatter: viewPageFormatFilePath,
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
  expectedDestination: "/fi/hakemus/liikunta_yleisavustushakemus/",
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


const registeredCommunityApplications_56 = {
  //success: baseFormRegisteredCommunity_56,
  draft: createFormData(baseFormRegisteredCommunity_56, saveDraft),
}

export {
  registeredCommunityApplications_56
}

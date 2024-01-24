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
const baseFormRegisteredCommunity_54: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-kaupunginkanslia-tyollisyysavust-form',
  formPath: '/fi/form/kaupunginkanslia-tyollisyysavust',
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
          value: '2025',
        },
        "edit-subventions-items-0-amount": {
          value: '5709,98',
        },
        "edit-compensation-purpose": {
          value: faker.lorem.sentences(4),
        },
        // muut samaan tarkoitukseen myönnetyt
        // muut samaan tarkoitukseen haetut
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
          value: "1",
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
          value: "0",
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
        'edit-vuosikokouksen-poytakirja-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vuosikokouksen_poytakirja_attachment]"]',
            resultValue: '.form-item-vuosikokouksen-poytakirja-attachment a',
          },
          value: PATH_VUOSIKOKOUKSEN_POYTAKIRJA,
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
  expectedDestination: "/fi/hakemus/kaupunginkanslia_tyollisyysavust/",
}

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values from 1st page',
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      // itemsToRemove: ['edit-bank-account-account-number-select'],
    },
    'webform_preview': {
      items: {},
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    // 'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
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

const registeredCommunityApplications_54 = {
  draft: baseFormRegisteredCommunity_54,
  // success: createFormData(baseFormRegisteredCommunity_54, sendApplication),
}

export {
  registeredCommunityApplications_54
}


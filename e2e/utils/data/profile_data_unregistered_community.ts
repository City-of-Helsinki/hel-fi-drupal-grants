import {fakerFI as faker} from '@faker-js/faker';
import {FormData, FormDataWithRemoveOptionalProps,} from "./test_data";
import {PROFILE_INPUT_DATA} from "./profile_input_data";
import {ATTACHMENTS} from "./attachment_data";
import {createFormData} from "../form_data_helpers";

const profileDataBase: FormData = {
  title: 'Save profile data',
  formSelector: 'grants-profile-unregistered-community',
  formPath: '/fi/oma-asiointi/hakuprofiili/muokkaa',
  formPages: {
    'onlyone': {
      items: {
        'companyname': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-companynamewrapper-companyname',
          },
          value: faker.company.name(),
          viewPageSelector: '.grants-profile',
        },
        'address-street': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-addresswrapper-0-address-street',
          },
          value: PROFILE_INPUT_DATA.address,
          viewPageSelector: '.grants-profile',
        },
        'address-postcode': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-addresswrapper-0-address-postcode',
          },
          value: PROFILE_INPUT_DATA.zipCode,
          viewPageSelector: '.grants-profile',
        },
        'address-city': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-addresswrapper-0-address-city',
          },
          value: PROFILE_INPUT_DATA.city,
          viewPageSelector: '.grants-profile',
        },
        'bankaccountwrapper': {
          role: 'multivalue',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-bankaccountwrapper',
          },
          multi: {
            buttonSelector: {
              type: 'add-more-button',
              name: 'data-drupal-selector',
              value: 'Lisää pankkitili',
              resultValue: 'edit-bankaccountwrapper-[INDEX]-bank',
            },
            //@ts-ignore
            items: {
              0:
                [
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-bankaccountwrapper-[INDEX]-bank-bankaccount',
                    },
                    value: PROFILE_INPUT_DATA.iban,
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'fileupload',
                    selector: {
                      type: 'locator',
                      name: 'data-drupal-selector',
                      value: '[name="files[bankAccountWrapper_[INDEX]_bank_confirmationFile]"]',
                      resultValue: '.form-item-bankaccountwrapper-[INDEX]-bank-confirmationfile a',
                    },
                    value: ATTACHMENTS.BANK_ACCOUNT_CONFIRMATION,
                    viewPageSkipValidation: true,
                  },
                ],
              1:
                [
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-bankaccountwrapper-[INDEX]-bank-bankaccount',
                    },
                    value: PROFILE_INPUT_DATA.iban2,
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'fileupload',
                    selector: {
                      type: 'locator',
                      name: 'data-drupal-selector',
                      value: '[name="files[bankAccountWrapper_[INDEX]_bank_confirmationFile]"]',
                      resultValue: '.form-item-bankaccountwrapper-[INDEX]-bank-confirmationfile a',
                    },
                    value: ATTACHMENTS.BANK_ACCOUNT_CONFIRMATION,
                    viewPageSkipValidation: true,
                  },
                ],
            },
          },
        },
        'official_name': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-officialwrapper-0-official-name',
          },
          value: PROFILE_INPUT_DATA.communityOfficial,
          viewPageSelector: '.grants-profile',
        },
        'official-role': {
          role: 'select',
          selector: {
            type: 'by-label',
            name: '',
            value: 'edit-officialwrapper-0-official-role',
          },
          value: PROFILE_INPUT_DATA.role,
          viewPageSelector: '.grants-profile',
        },
        'official-email': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-officialwrapper-0-official-email',
          },
          value: PROFILE_INPUT_DATA.email,
          viewPageSelector: '.grants-profile',
        },
        'official-phone': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-officialwrapper-0-official-phone',
          },
          value: PROFILE_INPUT_DATA.phone,
          viewPageSelector: '.grants-profile',
        },
        'submit': {
          role: 'button',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-submit',
          },
          viewPageSkipValidation: true,
        },
      }
    }
  },
  expectedDestination: "/fi/oma-asiointi/hakuprofiili",
  expectedErrors: {}
};

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values',
  viewPageSkipValidation: true,
  formPages: {
    'onlypage': {
      items: {},
      itemsToRemove: ['bankaccounts'],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'bankaccounts': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
  },
};

const ibanTestData: FormDataWithRemoveOptionalProps = {
  title: 'Invalid IBAN test',
  viewPageSkipValidation: true,
  formPages: {
    'onlyone': {
      items: {
        'bankaccountwrapper': {
          role: 'multivalue',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-bankaccountwrapper',
          },
          multi: {
            buttonSelector: {
              type: 'add-more-button',
              name: 'data-drupal-selector',
              value: 'Lisää pankkitili',
              resultValue: 'edit-bankaccountwrapper-[INDEX]-bank',
            },
            //@ts-ignore
            items: {
              0:
                [
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-bankaccountwrapper-[INDEX]-bank-bankaccount',
                    },
                    value: 'IBAN:FI1387667867985882',
                  },
                  {
                    role: 'fileupload',
                    selector: {
                      type: 'locator',
                      name: 'data-drupal-selector',
                      value: '[name="files[bankAccountWrapper_[INDEX]_bank_confirmationFile]"]',
                      resultValue: '.form-item-bankaccountwrapper-[INDEX]-bank-confirmationfile a',
                    },
                    value: ATTACHMENTS.BANK_ACCOUNT_CONFIRMATION,
                  },
                ],
              1:
                [
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-bankaccountwrapper-[INDEX]-bank-bankaccount',
                    },
                    value: '@FI5777266988169614',
                  },
                  {
                    role: 'fileupload',
                    selector: {
                      type: 'locator',
                      name: 'data-drupal-selector',
                      value: '[name="files[bankAccountWrapper_[INDEX]_bank_confirmationFile]"]',
                      resultValue: '.form-item-bankaccountwrapper-[INDEX]-bank-confirmationfile a',
                    },
                    value: ATTACHMENTS.BANK_ACCOUNT_CONFIRMATION,
                  },
                ]
            },
          },
        },
      },
    }
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-bankaccountwrapper-0-bank-bankaccount': 'Ei hyväksyttävä suomalainen IBAN: IBAN:FI1387667867985882',
    'edit-bankaccountwrapper-1-bank-bankaccount': 'Ei hyväksyttävä suomalainen IBAN: @FI5777266988169614'
  }
}

const profileDataUnregisteredCommunity = {
  success: profileDataBase,
  ibanTest: createFormData(profileDataBase, ibanTestData),
  // missingValues: createFormData(profileDataBase, missingValues)
}

export {
  profileDataUnregisteredCommunity
}

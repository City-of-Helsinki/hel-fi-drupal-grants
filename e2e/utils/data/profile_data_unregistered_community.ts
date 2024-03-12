import { fakerFI as faker } from '@faker-js/faker';
import { bankAccountConfirmationPath } from "../helpers";
import {
  FormData, FormDataWithRemoveOptionalProps,
} from "./test_data";
import { PROFILE_INPUT_DATA } from "./profile_input_data";
import { createFormData } from "../form_helpers";


const profileDataBase: FormData = {
  title: 'Profiledata: Successful',
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
        },
        'address-street': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-addresswrapper-0-address-street',
          },
          value: PROFILE_INPUT_DATA.address,
        },
        'address-postcode': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-addresswrapper-0-address-postcode',
          },
          value: PROFILE_INPUT_DATA.zipCode,
        },
        'address-city': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-addresswrapper-0-address-city',
          },
          value: PROFILE_INPUT_DATA.city,
        },
        'bankaccountwrapper': {
          role: 'multivalue',
          selector:
          {
            type: 'data-drupal-selector',
            name:
              'data-drupal-selector',
            value:
              'edit-bankaccountwrapper',
          },
          multi: {
            buttonSelector: {
              type: 'add-more-button',
              name:
                'data-drupal-selector',
              value:
                'Lisää pankkitili',
              resultValue:
                'edit-bankaccountwrapper-[INDEX]-bank',
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
                  },
                  {
                    role: 'fileupload',
                    selector: {
                      type: 'locator',
                      name: 'data-drupal-selector',
                      value: '[name="files[bankAccountWrapper_[INDEX]_bank_confirmationFile]"]',
                      resultValue: '.form-item-bankaccountwrapper-[INDEX]-bank-confirmationfile a',
                    },
                    value: bankAccountConfirmationPath,
                  },
                ],
            },
            expectedErrors: {
              // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
            }
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
        },
        'official-role': {
          role: 'select',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-officialwrapper-0-official-role',
          },
          value: '11',
        },
        'official-email': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-officialwrapper-0-official-email',
          },
          value: faker.internet.email(),
        },
        'official-phone': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-officialwrapper-0-official-phone',
          },
          value: faker.phone.number(),
        },
        'button-submit': {
          role: 'button',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-submit',
          }
        },
      }
    }
  },
  expectedDestination: "/fi/oma-asiointi/hakuprofiili",
  expectedErrors: {
    // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
  }
};

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values',
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
  formPages: {
    'onlyone': {
      items: {
        'bankaccountwrapper': {
          role: 'multivalue',
          selector:
          {
            type: 'data-drupal-selector',
            name:
              'data-drupal-selector',
            value:
              'edit-bankaccountwrapper',
          },
          multi: {
            buttonSelector: {
              type: 'add-more-button',
              name:
                'data-drupal-selector',
              value:
                'Lisää pankkitili',
              resultValue:
                'edit-bankaccountwrapper-[INDEX]-bank',
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
                      value: '[name="files[bankAccountWrapper_0_bank_confirmationFile]"]',
                      resultValue: '.form-item-bankaccountwrapper-0-bank-confirmationfile a',
                    },
                    value: bankAccountConfirmationPath,
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
                    value: bankAccountConfirmationPath,
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
    'edit-bankaccountwrapper-0-bank-bankaccount': ' Ei hyväksyttävä suomalainen IBAN: IBAN:FI1387667867985882',
    'edit-bankaccountwrapper-1-bank-bankaccount': ' Ei hyväksyttävä suomalainen IBAN: @FI5777266988169614'
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

import {fakerFI as faker} from '@faker-js/faker';
import {FormData, FormDataWithRemoveOptionalProps,} from "./test_data";
import {PROFILE_INPUT_DATA} from "./profile_input_data";
import {ATTACHMENTS} from "./attachment_data";
import {createFormData} from "../form_data_helpers";

const profileDataBase: FormData = {
  title: 'Save profile data',
  formSelector: 'grants-role-registered_community',
  formPath: '/fi/oma-asiointi/hakuprofiili/muokkaa',
  formPages: {
    'onlyone': {
      items: {
        'foundingyear': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-basicdetailswrapper-foundingyear',
          },
          value: '2016',
          viewPageSelector: '.grants-profile',
        },
        'companynameshort': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-basicdetailswrapper-companynameshort',
          },
          value: faker.company.buzzAdjective(),
          viewPageSelector: '.grants-profile',
        },
        'companyhomepage': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-basicdetailswrapper-companyhomepage',
          },
          value: faker.internet.domainName(),
          viewPageSelector: '.grants-profile',
        },
        'businesspurpose': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-basicdetailswrapper-businesspurpose',
          },
          value: faker.word.words(20),
          viewPageSelector: '.grants-profile',
        },
        'addresswrapper': {
          role: 'multivalue',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-addresswrapper',
          },
          multi: {
            buttonSelector: {
              type: 'add-more-button',
              name: 'data-drupal-selector',
              value: 'Lisää osoite',
              resultValue: 'edit-addresswrapper-[INDEX]-address',
            },
            // @ts-ignore
            items: {
              0:
                [
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-addresswrapper-[INDEX]-address-street',
                    },
                    value: PROFILE_INPUT_DATA.address,
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-addresswrapper-[INDEX]-address-postcode',
                    },
                    value: PROFILE_INPUT_DATA.zipCode,
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-addresswrapper-[INDEX]-address-city',
                    },
                    value: PROFILE_INPUT_DATA.city,
                    viewPageSelector: '.grants-profile',
                  },
                ],
              1:
                [
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-addresswrapper-[INDEX]-address-street',
                    },
                    value: faker.location.streetAddress(),
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-addresswrapper-[INDEX]-address-postcode',
                    },
                    value: faker.location.zipCode(),
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-addresswrapper-[INDEX]-address-city',
                    },
                    value: faker.location.city(),
                    viewPageSelector: '.grants-profile',
                  },
                ]
            }
          },
        },
        'officialwrapper': {
          role: 'multivalue',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-officialwrapper',
          },
          multi: {
            buttonSelector: {
              type: 'add-more-button',
              name: 'data-drupal-selector',
              value: 'Lisää vastuuhenkilö',
              resultValue: 'edit-officialwrapper-[INDEX]-official',
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
                      value: 'edit-officialwrapper-[INDEX]-official-name',
                    },
                    value: PROFILE_INPUT_DATA.communityOfficial,
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'select',
                    selector: {
                      type: 'by-label',
                      name: '',
                      value: 'edit-officialwrapper-[INDEX]-official-role',
                    },
                    value: PROFILE_INPUT_DATA.role,
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-officialwrapper-[INDEX]-official-email',
                    },
                    value: PROFILE_INPUT_DATA.email,
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-officialwrapper-[INDEX]-official-phone',
                    },
                    value: PROFILE_INPUT_DATA.phone,
                    viewPageSelector: '.grants-profile',
                  },
                ],
              1:
                [
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-officialwrapper-[INDEX]-official-name',
                    },
                    value: faker.person.fullName(),
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'select',
                    selector: {
                      type: 'by-label',
                      name: '',
                      value: 'edit-officialwrapper-[INDEX]-official-role',
                    },
                    value: 'Vastuuhenkilö',
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-officialwrapper-[INDEX]-official-email',
                    },
                    value: faker.internet.email().toLowerCase(),
                    viewPageSelector: '.grants-profile',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-officialwrapper-[INDEX]-official-phone',
                    },
                    value: faker.phone.number(),
                    viewPageSelector: '.grants-profile',
                  },
                ],
            }
          },
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
        'submit': {
          role: 'button',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-submit',
          },
          viewPageSkipValidation: true,
        },
      },
      expectedDestination: "/fi/oma-asiointi/hakuprofiili",
      expectedErrors: {}
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

const profileDataRegisteredCommunity = {
  success: profileDataBase,
  ibanTest: createFormData(profileDataBase, ibanTestData),
  // missingValues: createFormData(profileDataBase, missingValues)
}

export {
  profileDataRegisteredCommunity
}

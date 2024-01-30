import {fakerFI as faker} from '@faker-js/faker';
import {bankAccountConfirmationPath} from "../helpers";
import {
  FormData, FormDataWithRemoveOptionalProps,
} from "./test_data";
import {PROFILE_INPUT_DATA} from "./profile_input_data";
import {createFormData} from "../form_helpers";

const profileDataBase: FormData = {
  title: 'Profiledata: Successful',
  formSelector: 'grants-profile-registered-community',
  formPath: '/fi/oma-asiointi/hakuprofiili/muokkaa',
  formPages: {
    'onlyone': {
      items: {
        'foundingyear': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-foundingyearwrapper-foundingyear',
          },
          value: '2016',
        },
        'companynameshort': {
          role: 'input',
          selector:
            {
              type: 'data-drupal-selector',
              name:
                'data-drupal-selector',
              value:
                'edit-companynameshortwrapper-companynameshort',
            }
          ,
          value: faker.company.buzzAdjective(),
        },
        'companyhomepage': {
          role: 'input',
          selector:
            {
              type: 'data-drupal-selector',
              name:
                'data-drupal-selector',
              value:
                'edit-companyhomepagewrapper-companyhomepage',
            }
          ,
          value: faker.internet.domainName(),
        },
        'businesspurpose': {
          role: 'input',
          selector:
            {
              type: 'data-drupal-selector',
              name:
                'data-drupal-selector',
              value:
                'edit-businesspurposewrapper-businesspurpose',
            }
          ,
          value: faker.word.words(20),
        },
        'addresswrapper': {
          role: 'multivalue',
          selector:
            {
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
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-addresswrapper-[INDEX]-address-postcode',
                    },
                    value: PROFILE_INPUT_DATA.zipCode,
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-addresswrapper-[INDEX]-address-city',
                    },
                    value: PROFILE_INPUT_DATA.city,
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
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-addresswrapper-[INDEX]-address-postcode',
                    },
                    value: faker.location.zipCode(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-addresswrapper-[INDEX]-address-city',
                    },
                    value: faker.location.city(),
                  },
                ]
            }
            ,
            expectedErrors: {
              // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
            }
          }
          ,
        },
        'officialwrapper': {
          role: 'multivalue',
          selector:
            {
              type: 'data-drupal-selector',
              name:
                'data-drupal-selector',
              value:
                'edit-officialwrapper',
            },
          multi: {
            buttonSelector: {
              type: 'add-more-button',
              name:
                'data-drupal-selector',
              value:
                'Lisää vastuuhenkilö',
              resultValue:
                'edit-officialwrapper-[INDEX]-official',
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
                  },
                  {
                    role: 'select',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'name',
                      value: 'edit-officialwrapper-[INDEX]-official-role',
                    },
                    value: '11',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-officialwrapper-[INDEX]-official-email',
                    },
                    value: faker.internet.email(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-officialwrapper-[INDEX]-official-phone',
                    },
                    value: faker.phone.number(),
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
                  },
                  {
                    role: 'select',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-officialwrapper-[INDEX]-official-role',
                    },
                    value: '11',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-officialwrapper-[INDEX]-official-email',
                    },
                    value: faker.internet.email(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-officialwrapper-[INDEX]-official-phone',
                    },
                    value: faker.phone.number(),
                  },
                ],
            }
            ,
            expectedErrors: {
              // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
            }
          }
          ,
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
              1:
                [
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-bankaccountwrapper-[INDEX]-bank-bankaccount',
                    },
                    value: 'FI5777266988169614',
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
            }
            ,
            expectedErrors: {
              // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
            }
          }
          ,
        },
        'submit': {
          role: 'button',
          selector:
            {
              type: 'data-drupal-selector',
              name:
                'data-drupal-selector',
              value:
                'edit-actions-submit',
            }
        },
      },
      expectedDestination:
        "/fi/oma-asiointi/hakuprofiili",
      expectedErrors: {
        // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
      }

    }
  },
  expectedDestination:
    "/fi/oma-asiointi/hakuprofiili",
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

const profileDataRegisteredCommunity = {
  success: profileDataBase,
  // missingValues: createFormData(profileDataBase, missingValues)
}

export {
  profileDataRegisteredCommunity
}

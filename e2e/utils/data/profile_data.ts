import {fakerFI as faker} from '@faker-js/faker';
import path from "path";

const bankAccountConfirmationPath = path.join(__dirname, './test.pdf');

const PROFILE_FILE_PATH = '.auth/profile.json';


const profileDataPrivatePerson = {
  success: {
    title: 'Profiledata: Successful',
    formSelector: 'grants-profile-private-person',
    formPath: '/fi/oma-asiointi/hakuprofiili/muokkaa',
    formPages: {
      'onlypage': {
        items: {
          'streetaddress': {
            role: 'input',
            selector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-addresswrapper-0-address-street',
            },
            value: faker.location.streetAddress(),
          },
          'postcode': {
            role: 'input',
            selector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-addresswrapper-0-address-postcode',
            },
            value: faker.location.zipCode(),
          },
          'city': {
            role: 'input',
            selector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-addresswrapper-0-address-city',
            },
            value: 'Helsinki',
          },
          'phone': {
            role: 'input',
            selector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-phonewrapper-phone-number',
            },
            value: faker.phone.number(),
          },
          'bankaccounts': {
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
              items: {
                0: [
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-bankaccountwrapper-[INDEX]-bank-bankaccount',
                    },
                    value: 'FI1165467882414711',
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
                1: [
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
              },
              expectedErrors: {}
            },
          },
          'submit': {
            role: 'button',
            selector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-actions-submit',
            }
          },
        },
        expectedDestination: "/fi/oma-asiointi/hakuprofiili",
        expectedErrors: {
          // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
        }
      }
    },
    expectedDestination: "/fi/oma-asiointi/hakuprofiili",
    expectedErrors: {
      // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
    }
  },
  // valueMissing: {
  //   title: 'Profiledata: Values missing',
  //   formSelector: 'grants-profile-private-person',
  //   formPath: '/fi/oma-asiointi/hakuprofiili/muokkaa',
  //   formPages: [
  //     [
  //       {
  //         role: 'input',
  //         selector: {
  //           type: 'data-drupal-selector',
  //           name: 'data-drupal-selector',
  //           value: 'edit-addresswrapper-0-address-street',
  //         },
  //         value: faker.location.streetAddress(),
  //       },
  //       {
  //         role: 'input',
  //         selector: {
  //           type: 'data-drupal-selector',
  //           name: 'data-drupal-selector',
  //           value: 'edit-addresswrapper-0-address-postcode',
  //         },
  //         value: faker.location.zipCode(),
  //       },
  //       {
  //         role: 'input',
  //         selector: {
  //           type: 'data-drupal-selector',
  //           name: 'data-drupal-selector',
  //           value: 'edit-addresswrapper-0-address-city',
  //         },
  //         value: 'Helsinki',
  //       },
  //       {
  //         role: 'input',
  //         selector: {
  //           type: 'data-drupal-selector',
  //           name: 'data-drupal-selector',
  //           value: 'edit-phonewrapper-phone-number',
  //         },
  //         value: faker.phone.number(),
  //       },
  //       {
  //         role: 'button',
  //         selector: {
  //           type: 'data-drupal-selector',
  //           name: 'data-drupal-selector',
  //           value: 'edit-actions-submit',
  //         }
  //       },
  //     ]
  //   ],
  //   expectedDestination: "/fi/oma-asiointi/hakuprofiili/muokkaa",
  //   expectedErrors: {
  //     "edit-bankaccountwrapper": `Sinun tulee lisätä vähintään yksi pankkitili`
  //   }
  // }
}

const profileDataUnregisteredCommunity = {
  success: {
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
            value: faker.location.streetAddress(),
          },
          'address-postcode': {
            role: 'input',
            selector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-addresswrapper-0-address-postcode',
            },
            value: faker.location.zipCode(),
          },
          'address-city': {
            role: 'input',
            selector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-addresswrapper-0-address-city',
            },
            value: 'Helsinki',
          },
          'bankaccount': {
            role: 'input',
            selector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-bankaccountwrapper-0-bank-bankaccount',
            },
            value: 'FI1165467882414711',
          },
          'bankconfirm': {
            role: 'fileupload',
            selector: {
              type: 'locator',
              name: 'data-drupal-selector',
              value: '[name="files[bankAccountWrapper_0_bank_confirmationFile]"]',
              resultValue: '.form-item-bankaccountwrapper-0-bank-confirmationfile a',
            },
            value: bankAccountConfirmationPath,
          },
          'official_name': {
            role: 'input',
            selector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-officialwrapper-0-official-name',
            },
            value: faker.person.fullName(),
          },
          'official-role': {
            role: 'select',
            selector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-officialwrapper-0-official-role',
            },
            value: 11,
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
        },
        expectedDestination: "/fi/oma-asiointi/hakuprofiili",
        expectedErrors: {
          // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
        }
      }
    },
    expectedDestination: "/fi/oma-asiointi/hakuprofiili",
    expectedErrors: {
      // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
    }
  }
}

const profileDataRegisteredCommunity = {
    success: {
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
                  name:
                    'data-drupal-selector',
                  value:
                    'edit-addresswrapper',
                },
              multi: {
                buttonSelector: {
                  type: 'add-more-button',
                  name:
                    'data-drupal-selector',
                  value:
                    'Lisää osoite',
                  resultValue:
                    'edit-addresswrapper-[INDEX]-address',
                }
                ,
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
                        value: faker.person.fullName(),
                      },
                      {
                        role: 'select',
                        selector: {
                          type: 'data-drupal-selector',
                          name: 'data-drupal-selector',
                          value: 'edit-officialwrapper-[INDEX]-official-role',
                        },
                        value: faker.number.int({min: 1, max: 12}),
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
                        value: 11,
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
                        value: 'FI1165467882414711',
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
    }
  }
;

export {
  profileDataPrivatePerson,
  profileDataUnregisteredCommunity,
  profileDataRegisteredCommunity,
  PROFILE_FILE_PATH
}

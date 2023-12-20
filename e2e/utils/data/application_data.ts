import {fakerFI as faker} from "@faker-js/faker";


const privatePersonApplications = {
  48: {
    success: {
      title: 'Form submit',
      formSelector: 'webform-submission-kuva-projekti-form',
      formPath: '/fi/form/kuva-projekti',
      formPages: {
        "1_hakijan_tiedot": {
          items: {
            "bank-account": {
              role: 'select',
              selector: {
                type: 'dom-id-first',
                name: 'bank-account-selector',
                value: '#edit-bank-account-account-number-select',
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
          expectedErrors: {
            // 'bank-account': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
          },
          expectedDestination: "/fi/hakemus/kuva_projekti/",
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
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-subventions-items-0-amount',
              },
              value: '5709,98',
            },
            "ensisijainen_taiteenala": {
              role: 'select',
              selector: {
                type: 'dom-id-first',
                name: 'data-drupal-selector',
                value: '#edit-ensisijainen-taiteen-ala',
              },
              value: 'use-random-value',
            },
            "hankkeen_nimi": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-hankkeen-nimi',
              },
              value: faker.lorem.words(3).toLocaleUpperCase(),
            },
            "kyseessa_on_festivaali": {
              role: 'radio',
              selector: {
                type: 'dom-id-label',
                name: 'data-drupal-selector',
                value: 'edit-kyseessa-on-festivaali-tai-tapahtuma-1',
              },
              value: "1",
            },
            "edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti',
              },
              value: faker.lorem.words(30),
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
                    name:
                      'data-drupal-selector',
                    value:
                      'Lisää uusi myönnetty avustus',
                    resultValue:
                      'edit-myonnetty-avustus-items-[INDEX]',
                  },
                  items: {
                    0: [
                        {
                          role: 'select',
                          selector: {
                            type: 'dom-id-first',
                            name: 'bank-account-selector',
                            value: '#edit-myonnetty-avustus-items-[INDEX]-item-issuer',
                          },
                          value: 'use-random-value',
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
                          value: faker.date.past().getFullYear(),
                        },
                        {
                          role: 'input',
                          selector: {
                            type: 'data-drupal-selector',
                            name: 'data-drupal-selector',
                            value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                          },
                          value: faker.finance.amount({min: 100, max:10000, autoFormat: true}),
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
                    //     {
                    //       role: 'select',
                    //       selector: {
                    //         type: 'name',
                    //         name: 'bank-account-selector',
                    //         value: 'myonnetty_avustus[items][[INDEX]][_item_][issuer]',
                    //       },
                    //       value: 'use-random-value',
                    //     },
                    //     {
                    //       role: 'input',
                    //       selector: {
                    //         type: 'data-drupal-selector',
                    //         name: 'data-drupal-selector',
                    //         value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer-name',
                    //       },
                    //       value: faker.lorem.words(2).toUpperCase(),
                    //     },
                    //     {
                    //       role: 'input',
                    //       selector: {
                    //         type: 'data-drupal-selector',
                    //         name: 'data-drupal-selector',
                    //         value: 'edit-myonnetty-avustus-items-[INDEX]-item-year',
                    //       },
                    //       value: faker.date.past().getFullYear(),
                    //     },
                    //     {
                    //       role: 'input',
                    //       selector: {
                    //         type: 'data-drupal-selector',
                    //         name: 'data-drupal-selector',
                    //         value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                    //       },
                    //       value: faker.finance.amount({min: 100, max:10000, autoFormat: true}),
                    //     },
                    //     {
                    //       role: 'input',
                    //       selector: {
                    //         type: 'data-drupal-selector',
                    //         name: 'data-drupal-selector',
                    //         value: 'edit-myonnetty-avustus-items-[INDEX]-item-purpose',
                    //       },
                    //       value: faker.lorem.words(30),
                    //     },
                    //   ],
                  }
                  ,
                  expectedErrors: {
                    // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
                  }
                }
              },
            },
            "nextbutton": {
              role: 'button',
              selector: {
                type: 'wizard-next',
                name: 'data-drupal-selector',
                value: 'Seuraava',
              }
            },
          },
          expectedErrors: {},
          expectedDestination: "/fi/hakemus/kuva_projekti/",
        },
        '3_yhteison_tiedot': {
          items: {
            "edit-members-applicant-person-global": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-person-global',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-members-applicant-person-local": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-person-local',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-members-applicant-community-global": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-community-global',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-members-applicant-community-local": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-community-local',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-kokoaikainen-henkilosto": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-kokoaikainen-henkilosto',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-kokoaikainen-henkilotyovuosia": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-kokoaikainen-henkilotyovuosia',
              },
              value: faker.number.float({min: 1, max: 100, precision: 2}),
            },
            "edit-osa-aikainen-henkilosto": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-osa-aikainen-henkilosto',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-osa-aikainen-henkilotyovuosia": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-osa-aikainen-henkilotyovuosia',
              },
              value: faker.number.float({min: 1, max: 100, precision: 2}),
            },
            "edit-vapaaehtoinen-henkilosto": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-vapaaehtoinen-henkilosto',
              },
              value: faker.number.int({min:1, max: 100}),
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
          expectedErrors: {
            // 'bank-account': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
          },
          expectedDestination: "/fi/hakemus/kuva_projekti/",
        }
      },
      expectedErrors: {
        // 'bank-account': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
        // 'haettu-vuosi': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
        //  'feativaalitaitapahtuma': 'Virhe sivulla 2. Avustustiedot: Kyseessä on festivaali tai tapahtuma kenttä on pakollinen.',
        // 'lyhyt-esittely': 'Virhe sivulla 2. Avustustiedot: Hankkeen tai toiminnan lyhyt esittelyteksti kenttä on pakollinen.'
      },
      expectedDestination: "/fi/hakemus/kuva_projekti/",
    },
    // missing_values: {
    //   title: 'Missing required values',
    //   formSelector: 'webform-submission-kuva-projekti-form',
    //   formPath: '/fi/form/kuva-projekti',
    //   formPages: [
    //     [
    //       // {
    //       //   role: 'select',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-bank-account-account-number-select',
    //       //   },
    //       //   value: 'use-random-value',
    //       // },
    //       {
    //         role: 'button',
    //         selector: {
    //           type: 'data-drupal-selector',
    //           name: 'data-drupal-selector',
    //           value: 'edit-actions-wizard-next',
    //         }
    //       },
    //     ],
    //     [
    //       // {
    //       //   role: 'select',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-acting-year',
    //       //   },
    //       //   value: 'use-random-value',
    //       // },
    //       // {
    //       //   role: 'input',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-subventions-items-0-amount',
    //       //   },
    //       //   value: '5709,98',
    //       // },
    //       // {
    //       //   role: 'select',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-ensisijainen-taiteen-ala',
    //       //   },
    //       //   value: 'use-random-value',
    //       // },
    //       // {
    //       //   role: 'input',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-hankkeen-nimi',
    //       //   },
    //       //   value: faker.lorem.words(3).toLocaleUpperCase(),
    //       // },
    //       // {
    //       //   role: 'input',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-kyseessa-on-festivaali-tai-tapahtuma-1',
    //       //   },
    //       //   value: "1",
    //       // },
    //       {
    //         role: 'button',
    //         selector: {
    //           type: 'data-drupal-selector',
    //           name: 'data-drupal-selector',
    //           value: 'edit-actions-wizard-next',
    //         }
    //       },
    //     ]
    //   ],
    //   expectedErrors: {
    //     'tilinumero':'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
    //   },
    //   expectedDestination: "/fi/hakemus/kuva_projekti/",
    // }
  }
}

const registeredCommunityApplications = {
  48: {
    success: {
      title: 'Form submit',
      formSelector: 'webform-submission-kuva-projekti-form',
      formPath: '/fi/form/kuva-projekti',
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
            "bank-account": {
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
          expectedErrors: {
            // 'bank-account': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
          },
          expectedDestination: "/fi/hakemus/kuva_projekti/",
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
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-subventions-items-0-amount',
              },
              value: '5709,98',
            },
            "ensisijainen_taiteenala": {
              role: 'select',
              selector: {
                type: 'dom-id-first',
                name: 'data-drupal-selector',
                value: '#edit-ensisijainen-taiteen-ala',
              },
              value: 'use-random-value',
            },
            "hankkeen_nimi": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-hankkeen-nimi',
              },
              value: faker.lorem.words(3).toLocaleUpperCase(),
            },
            "kyseessa_on_festivaali": {
              role: 'radio',
              selector: {
                type: 'dom-id-label',
                name: 'data-drupal-selector',
                value: 'edit-kyseessa-on-festivaali-tai-tapahtuma-1',
              },
              value: "1",
            },
            "edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti',
              },
              value: faker.lorem.words(30),
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
                    name:
                      'data-drupal-selector',
                    value:
                      'Lisää uusi myönnetty avustus',
                    resultValue:
                      'edit-myonnetty-avustus-items-[INDEX]',
                  },
                  items: {
                    0: [
                      {
                        role: 'select',
                        selector: {
                          type: 'dom-id-first',
                          name: 'bank-account-selector',
                          value: '#edit-myonnetty-avustus-items-[INDEX]-item-issuer',
                        },
                        value: 'use-random-value',
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
                        value: faker.date.past().getFullYear(),
                      },
                      {
                        role: 'input',
                        selector: {
                          type: 'data-drupal-selector',
                          name: 'data-drupal-selector',
                          value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                        },
                        value: faker.finance.amount({min: 100, max:10000, autoFormat: true}),
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
                    //     {
                    //       role: 'select',
                    //       selector: {
                    //         type: 'name',
                    //         name: 'bank-account-selector',
                    //         value: 'myonnetty_avustus[items][[INDEX]][_item_][issuer]',
                    //       },
                    //       value: 'use-random-value',
                    //     },
                    //     {
                    //       role: 'input',
                    //       selector: {
                    //         type: 'data-drupal-selector',
                    //         name: 'data-drupal-selector',
                    //         value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer-name',
                    //       },
                    //       value: faker.lorem.words(2).toUpperCase(),
                    //     },
                    //     {
                    //       role: 'input',
                    //       selector: {
                    //         type: 'data-drupal-selector',
                    //         name: 'data-drupal-selector',
                    //         value: 'edit-myonnetty-avustus-items-[INDEX]-item-year',
                    //       },
                    //       value: faker.date.past().getFullYear(),
                    //     },
                    //     {
                    //       role: 'input',
                    //       selector: {
                    //         type: 'data-drupal-selector',
                    //         name: 'data-drupal-selector',
                    //         value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                    //       },
                    //       value: faker.finance.amount({min: 100, max:10000, autoFormat: true}),
                    //     },
                    //     {
                    //       role: 'input',
                    //       selector: {
                    //         type: 'data-drupal-selector',
                    //         name: 'data-drupal-selector',
                    //         value: 'edit-myonnetty-avustus-items-[INDEX]-item-purpose',
                    //       },
                    //       value: faker.lorem.words(30),
                    //     },
                    //   ],
                  }
                  ,
                  expectedErrors: {
                    // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
                  }
                }
              },
            },
            "nextbutton": {
              role: 'button',
              selector: {
                type: 'wizard-next',
                name: 'data-drupal-selector',
                value: 'Seuraava',
              }
            },
          },
          expectedErrors: {},
          expectedDestination: "/fi/hakemus/kuva_projekti/",
        },
        '3_yhteison_tiedot': {
          items: {
            "edit-members-applicant-person-global": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-person-global',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-members-applicant-person-local": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-person-local',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-members-applicant-community-global": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-community-global',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-members-applicant-community-local": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-community-local',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-kokoaikainen-henkilosto": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-kokoaikainen-henkilosto',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-kokoaikainen-henkilotyovuosia": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-kokoaikainen-henkilotyovuosia',
              },
              value: faker.number.float({min: 1, max: 100, precision: 2}),
            },
            "edit-osa-aikainen-henkilosto": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-osa-aikainen-henkilosto',
              },
              value: faker.number.int({min:1, max: 100}),
            },
            "edit-osa-aikainen-henkilotyovuosia": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-osa-aikainen-henkilotyovuosia',
              },
              value: faker.number.float({min: 1, max: 100, precision: 2}),
            },
            "edit-vapaaehtoinen-henkilosto": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-vapaaehtoinen-henkilosto',
              },
              value: faker.number.int({min:1, max: 100}),
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
          expectedErrors: {
            // 'bank-account': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
          },
          expectedDestination: "/fi/hakemus/kuva_projekti/",
        }
      },
      expectedErrors: {
        // 'bank-account': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
        // 'haettu-vuosi': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
        //  'feativaalitaitapahtuma': 'Virhe sivulla 2. Avustustiedot: Kyseessä on festivaali tai tapahtuma kenttä on pakollinen.',
        // 'lyhyt-esittely': 'Virhe sivulla 2. Avustustiedot: Hankkeen tai toiminnan lyhyt esittelyteksti kenttä on pakollinen.'
      },
      expectedDestination: "/fi/hakemus/kuva_projekti/",
    },
    // missing_values: {
    //   title: 'Missing required values',
    //   formSelector: 'webform-submission-kuva-projekti-form',
    //   formPath: '/fi/form/kuva-projekti',
    //   formPages: [
    //     [
    //       // {
    //       //   role: 'select',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-bank-account-account-number-select',
    //       //   },
    //       //   value: 'use-random-value',
    //       // },
    //       {
    //         role: 'button',
    //         selector: {
    //           type: 'data-drupal-selector',
    //           name: 'data-drupal-selector',
    //           value: 'edit-actions-wizard-next',
    //         }
    //       },
    //     ],
    //     [
    //       // {
    //       //   role: 'select',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-acting-year',
    //       //   },
    //       //   value: 'use-random-value',
    //       // },
    //       // {
    //       //   role: 'input',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-subventions-items-0-amount',
    //       //   },
    //       //   value: '5709,98',
    //       // },
    //       // {
    //       //   role: 'select',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-ensisijainen-taiteen-ala',
    //       //   },
    //       //   value: 'use-random-value',
    //       // },
    //       // {
    //       //   role: 'input',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-hankkeen-nimi',
    //       //   },
    //       //   value: faker.lorem.words(3).toLocaleUpperCase(),
    //       // },
    //       // {
    //       //   role: 'input',
    //       //   selector: {
    //       //     type: 'data-drupal-selector',
    //       //     name: 'data-drupal-selector',
    //       //     value: 'edit-kyseessa-on-festivaali-tai-tapahtuma-1',
    //       //   },
    //       //   value: "1",
    //       // },
    //       {
    //         role: 'button',
    //         selector: {
    //           type: 'data-drupal-selector',
    //           name: 'data-drupal-selector',
    //           value: 'edit-actions-wizard-next',
    //         }
    //       },
    //     ]
    //   ],
    //   expectedErrors: {
    //     'tilinumero':'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
    //   },
    //   expectedDestination: "/fi/hakemus/kuva_projekti/",
    // }
  }
}

export {
  privatePersonApplications,
  registeredCommunityApplications
}

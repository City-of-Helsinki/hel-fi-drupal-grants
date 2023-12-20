import {fakerFI as faker} from "@faker-js/faker";
import {
  FormData,
  Selector,
  FormField,
  FormDataWithRemove,
  FormDataWithRemoveOptionalProps
} from "./test_data";
import {createFormData} from "../form_helpers";
import {PATH_TO_TEST_PDF} from "../helpers";

const baseFormPrivatePerson_48: FormData = {
  title: 'Success',
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
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '2_avustustiedot',
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
                name: 'data-drupal-selector',
                value: 'Lisää uusi myönnetty avustus',
                resultValue: 'edit-myonnetty-avustus-items-[INDEX]',
              },
              items: {
                0: [
                  {
                    role: 'select',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'name',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer'
                    },
                    value: '3',
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
                    value: faker.date.past().getFullYear().toString(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                    },
                    value: faker.finance.amount({
                      min: 100,
                      max: 10000,
                      autoFormat: true
                    }),
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
                //   {
                //     role: 'select',
                //     selector: {
                //       type: 'data-drupal-selector',
                //       name: 'name',
                //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer',
                //     },
                //     value: 'use-random-value',
                //   },
                //   {
                //     role: 'input',
                //     selector: {
                //       type: 'data-drupal-selector',
                //       name: 'data-drupal-selector',
                //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer-name',
                //     },
                //     value: faker.lorem.words(2).toUpperCase(),
                //   },
                //   {
                //     role: 'input',
                //     selector: {
                //       type: 'data-drupal-selector',
                //       name: 'data-drupal-selector',
                //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-year',
                //     },
                //     value: faker.date.past().getFullYear().toString(),
                //   },
                //   {
                //     role: 'input',
                //     selector: {
                //       type: 'data-drupal-selector',
                //       name: 'data-drupal-selector',
                //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                //     },
                //     value: faker.finance.amount({
                //       min: 100,
                //       max: 10000,
                //       autoFormat: true
                //     }),
                //   },
                //   {
                //     role: 'input',
                //     selector: {
                //       type: 'data-drupal-selector',
                //       name: 'data-drupal-selector',
                //       value: 'edit-myonnetty-avustus-items-[INDEX]-item-purpose',
                //     },
                //     value: faker.lorem.words(30),
                //   },
                // ],
              },
              expectedErrors: {}
            }
          },
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
        "edit-members-applicant-person-global": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-members-applicant-person-global',
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-members-applicant-person-local": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-members-applicant-person-local',
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-members-applicant-community-global": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-members-applicant-community-global',
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-members-applicant-community-local": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-members-applicant-community-local',
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-kokoaikainen-henkilosto": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-kokoaikainen-henkilosto',
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-kokoaikainen-henkilotyovuosia": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-kokoaikainen-henkilotyovuosia',
          },
          value: faker.number.float({
            min: 1,
            max: 100,
            precision: 2
          }).toString(),
        },
        "edit-osa-aikainen-henkilosto": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-osa-aikainen-henkilosto',
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-osa-aikainen-henkilotyovuosia": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-osa-aikainen-henkilotyovuosia',
          },
          value: faker.number.float({
            min: 1,
            max: 100,
            precision: 2
          }).toString(),
        },
        "edit-vapaaehtoinen-henkilosto": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-vapaaehtoinen-henkilosto',
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '4_suunniteltu_toiminta',
          }
        },
      },
    },
    "4_suunniteltu_toiminta": {
      items: {
        "edit-tapahtuma-tai-esityspaivien-maara-helsingissa": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Labels',
            details: {
              label: 'Tapahtuma- tai esityspäivien määrä Helsingissä'
            },
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-esitykset-maara-helsingissa": {
          role: 'input',
          selector: {
            type: 'role-label',
            name: 'Labels / Groups',
            details: {
              role: 'group',
              options: {
                name: 'Määrä Helsingissä'
              },
              label: 'Esitykset'
            },
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-tyopaja-maara-helsingissa": {
          role: 'input',
          selector: {
            type: 'role-label',
            name: 'Labels / Groups',
            details: {
              role: 'group',
              options: {
                name: 'Määrä Helsingissä'
              },
              label: 'Työpaja tai muu osallistava toimintamuoto'
            },
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-nayttelyt-maara-helsingissa": {
          role: 'input',
          selector: {
            type: 'role-label',
            name: 'Labels / Groups',
            details: {
              role: 'group',
              options: {
                name: 'Määrä Helsingissä'
              },
              label: 'Näyttelyt'
            },
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-esitykset-maara-kaikkiaan": {
          role: 'input',
          selector: {
            type: 'role-label',
            name: 'Labels / Groups',
            details: {
              role: 'group',
              options: {
                name: 'Määrä kaikkiaan'
              },
              label: 'Esitykset'
            },
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-nayttelyt-maara-kaikkiaan": {
          role: 'input',
          selector: {
            type: 'role-label',
            name: 'Labels / Groups',
            details: {
              role: 'group',
              options: {
                name: 'Määrä kaikkiaan'
              },
              label: 'Näyttelyt'
            },
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-tyopaja-maara-kaikkiaan": {
          role: 'input',
          selector: {
            type: 'role-label',
            name: 'Labels / Groups',
            details: {
              role: 'group',
              options: {
                name: 'Määrä kaikkiaan'
              },
              label: 'Työpaja tai muu osallistava toimintamuoto'
            },
          },
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-maara-helsingissa": {
          role: 'input',
          selector: {
            type: 'role',
            name: 'Role',
            details: {
              role: 'textbox',
              options: {
                name: 'Määrä kaikkiaan'
              },
              label: 'Kävijämäärä Helsingissä'
            },
          },
          value: faker.number.int({min: 100, max: 10000}).toString(),
        },
        "edit-maara-kaikkiaan": {
          role: 'input',
          selector: {
            type: 'role',
            name: 'Role',
            details: {
              role: 'textbox',
              options: {
                name: 'Määrä kaikkiaan'
              }
            },
          },
          value: faker.number.int({min: 1000, max: 100000}).toString(),
        },
        "edit-kantaesitysten-maara": {
          role: 'input',
          selector: {
            type: 'role',
            name: 'Role',
            details: {
              role: 'textbox',
              options: {
                name: 'Kantaesitysten määrä'
              }
            },
          },
          value: faker.number.int({min: 10, max: 100}).toString(),
        },
        "edit-ensi-iltojen-maara-helsingissa": {
          role: 'input',
          selector: {
            type: 'role',
            name: 'Role',
            details: {
              role: 'textbox',
              options: {
                name: 'Ensi-iltojen määrä Helsingissä'
              }
            },
          },
          value: faker.number.int({min: 10, max: 100}).toString(),
        },
        "edit-ensimmainen-yleisolle-avoimen-tilaisuuden-paikka-helsingissa": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Tilan nimi'
            },
          },
          value: faker.company.buzzPhrase(),
        },
        "edit-postinumero": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Postinumero'
            },
          },
          value: faker.number.int({min: 10000, max: 99999}).toString(),
        },
        "edit-kyseessa-on-kaupungin-omistama-tila-1": {
          role: 'radio',
          selector: {
            type: 'text',
            name: 'Label',
            details: {
              text: 'Ei',
              options: {
                exact: true
              }
            },
          },
          value: "1",
        },
        "edit-ensimmaisen-yleisolle-avoimen-tilaisuuden-paivamaara": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Ensimmäisen yleisölle avoimen tilaisuuden päivämäärä'
            },
          },
          value: "2023-11-01",
        },
        "edit-festivaalin-tai-tapahtuman-kohdalla-tapahtuman-paivamaarat": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Festivaalin tai tapahtuman kohdalla tapahtuman päivämäärät'
            },
          },
          value: faker.lorem.words(10),
        },
        "edit-hanke-alkaa": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Hanke alkaa'
            },
          },
          value: "2023-11-01",
        },
        "edit-hanke-loppuu": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Hanke loppuu'
            },
          },
          value: "2023-12-01",
        },
        "edit-laajempi-hankekuvaus": {
          role: 'input',
          selector: {
            type: 'role',
            name: 'Role',
            details: {
              role: 'textbox',
              options: {
                name: 'Laajempi hankekuvaus'
              }
            },
          },
          value: faker.lorem.sentences(3),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '5_toiminnan_lahtokohdat',
          }
        },
      },
    },
    "5_toiminnan_lahtokohdat": {
      items: {
        "edit-toiminta-kohderyhmat": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Keitä toiminnalla tavoitellaan? Miten kyseiset kohderyhmät aiotaan tavoittaa ja mitä osaamista näiden kanssa työskentelyyn on?'
            },
          },
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-yhteistyokumppanit": {
          role: 'input',
          selector: {
            type: 'role',
            name: 'Role',
            details: {
              role: 'textbox',
              options: {
                name: 'Nimeä keskeisimmät yhteistyökumppanit ja kuvaa yhteistyön muotoja ja ehtoja'
              }
            },
          },
          value: faker.lorem.sentences(3),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '6_talous',
          }
        },
      },
    },
    "6_talous": {
      items: {
        "edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-1": {
          role: 'radio',
          selector: {
            type: 'text',
            name: 'Label',
            details: {
              text: 'Kyllä',
              options: {
                exact: true
              }
            },
          },
          value: "1",
        },
        "edit-budget-static-income-plannedothercompensations": {
          role: 'input',
          selector: {
            type: 'role',
            name: 'Role',
            details: {
              role: 'textbox',
              options: {
                name: 'Muut avustukset (€)'
              }
            },
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-income-sponsorships": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-income-sponsorships',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-income-entryfees": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-income-entryfees',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-income-sales": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-income-sales',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-income-ownfunding": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-income-ownfunding',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-cost-personnelsidecosts": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-cost-personnelsidecosts',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-cost-performerfees": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-cost-performerfees',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-cost-otherfees": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-cost-otherfees',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-cost-showcosts": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-cost-showcosts',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-cost-travelcosts": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-cost-travelcosts',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-cost-transportcosts": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-cost-transportcosts',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-cost-equipment": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-cost-equipment',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-cost-premises": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-cost-premises',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-static-cost-marketing": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-budget-static-cost-marketing',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-other-cost-items-0-item-label": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Kuvaus menosta'
            },
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-budget-other-cost-items-0-item-value": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Määrä (€)'
            },
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-muu-huomioitava-panostus": {
          role: 'input',
          selector: {
            type: 'label',
            name: 'Label',
            details: {
              label: 'Sisältyykö toiminnan toteuttamiseen jotain muuta rahanarvoista panosta tai vaihtokauppaa, joka ei käy ilmi budjetista?'
            },
          },
          value: faker.lorem.sentences(3),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'lisatiedot_ja_liitteet',
          }
        },
      },
    },
    "lisatiedot_ja_liitteet": {
      items: {
        "edit-budget-static-income-plannedothercompensations": {
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
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-extra-info',
          },
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
  expectedDestination: "/fi/hakemus/kuva_projekti/",
}

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values from 1st page',
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: ['bank-account'],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'bank-account': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
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

const privatePersonApplications = {
  48: {
    success: baseFormPrivatePerson_48,
    draft: createFormData(baseFormPrivatePerson_48,saveDraft),
    // missing_values:  createFormData(baseFormPrivatePerson_48,missingValues),
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
                        value: faker.finance.amount({
                          min: 100,
                          max: 10000,
                          autoFormat: true
                        }),
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
              value: faker.number.int({min: 1, max: 100}),
            },
            "edit-members-applicant-person-local": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-person-local',
              },
              value: faker.number.int({min: 1, max: 100}),
            },
            "edit-members-applicant-community-global": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-community-global',
              },
              value: faker.number.int({min: 1, max: 100}),
            },
            "edit-members-applicant-community-local": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-members-applicant-community-local',
              },
              value: faker.number.int({min: 1, max: 100}),
            },
            "edit-kokoaikainen-henkilosto": {
              role: 'input',
              selector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-kokoaikainen-henkilosto',
              },
              value: faker.number.int({min: 1, max: 100}),
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
              value: faker.number.int({min: 1, max: 100}),
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
              value: faker.number.int({min: 1, max: 100}),
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
  registeredCommunityApplications,

}

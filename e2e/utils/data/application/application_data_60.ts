import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PROFILE_INPUT_DATA} from "../profile_input_data";
import {ATTACHMENTS} from "../attachment_data";
import {createFormData} from "../../form_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatCurrency,
  viewPageFormatLowerCase,
  viewPageFormatDate,
  viewPageFormatNumber,
  viewPageFormatFilePath
} from "../../view_page_formatters";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_60: FormData = {
  title: 'Save as draft.',
  formSelector: 'webform-submission-liikunta-toiminta-ja-tilankaytto-form',
  formPath: '/fi/form/liikunta-toiminta-ja-tilankaytto',
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
        "edit-hakijan-tyyppi": {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: 'hakijan-tyyppi-selector',
            value: '#edit-hakijan-tyyppi',
          },
          value: 'Liikuntaseura',
        },
        "edit-acting-year": {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: 'acting-year-selector',
            value: '#edit-acting-year',
          },
          value: '2024',
        },
        "edit-subventions-items-0-amount": {
          role: 'number-input',
          value: '5709,98',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatCurrency
        },
        "edit-subventions-items-1-amount": {
          role: 'number-input',
          value: '5809,98',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatCurrency
        },
        "edit-compensation-boolean-1": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-compensation-boolean-1',
          },
          value: "Olen saanut Helsingin kaupungilta avustusta samaan käyttötarkoitukseen edellisenä vuonna.",
        },
        "edit-compensation-explanation": {
          value: faker.lorem.sentences(4),
        },
        "edit-compensation-purpose": {
          value: faker.lorem.sentences(4),
        },

        "edit-myonnetty-avustus": {
          role: 'dynamicmultivalue',
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
            multi: {
              buttonSelector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-myonnetty-avustus-add-submit',
                resultValue: 'edit-myonnetty-avustus-items-[INDEX]',
              },
              //@ts-ignore
              items: {
                0: [
                  {
                    role: 'select',
                    selector: {
                      type: 'by-label',
                      name: '',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer',
                    },
                    value: 'Valtio',
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
                      type: 'data-drupal-selector-sequential',
                      name: 'data-drupal-selector-sequential',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                    },
                    value: faker.number.float({
                      min: 1000,
                      max: 10000,
                      precision: 2
                    }).toString(),
                    viewPageFormatter: viewPageFormatCurrency,
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
                1: [
                  {
                    role: 'select',
                    selector: {
                      type: 'by-label',
                      name: '',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-issuer',
                    },
                    value: 'EU',
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
                      type: 'data-drupal-selector-sequential',
                      name: 'data-drupal-selector-sequential',
                      value: 'edit-myonnetty-avustus-items-[INDEX]-item-amount',
                    },
                    value: faker.number.float({
                      min: 1000,
                      max: 10000,
                      precision: 2
                    }).toString(),
                    viewPageFormatter: viewPageFormatCurrency,
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
              },
              expectedErrors: {}
            }
          },
        },

        "edit-tuntimaara-yhteensa": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-vuokrat-yhteensa": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-seuraavalle-vuodelle-suunniteltu-muutos-tilojen-kaytossa-tunnit-": {
          value: faker.lorem.words(4),
          viewPageSelector: '.form-item-seuraavalle-vuodelle-suunniteltu-muutos-tilojen-kaytossa-tunnit-',
        },
        'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-add-submit',
              resultValue: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-premisename',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-datebegin',
                  },
                  value: "2023-11-01",
                  viewPageFormatter: viewPageFormatDate,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-dateend',
                  },
                  value: "2023-12-01",
                  viewPageFormatter: viewPageFormatDate,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-tenantname',
                  },
                  value: faker.person.fullName(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-hours',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-sum',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatCurrency,
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-premisename',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-datebegin',
                  },
                  value: "2023-11-01",
                  viewPageFormatter: viewPageFormatDate,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-dateend',
                  },
                  value: "2023-12-01",
                  viewPageFormatter: viewPageFormatDate,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-tenantname',
                  },
                  value: faker.person.fullName(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-hours',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-seuran-yhdistyksen-saamat-vuokrat-edellisen-kalenterivuoden-ajal-items-[INDEX]-item-sum',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatCurrency,
                },
              ],
            },
            expectedErrors: {}
          },
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '3_yhteison_tiedot',
          },
          viewPageSkipValidation: true,
        },
      },
    },
    "3_yhteison_tiedot": {
      items: {
        "edit-miehet-20-63-vuotiaat-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-joista-helsinkilaisia-miehet-20-63-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-naiset-20-63-vuotiaat-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-joista-helsinkilaisia-naiset-20-63-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-muut-20-63-vuotiaat-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-joista-helsinkilaisia-muut-20-63-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-miehet-64-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-joista-helsinkilaisia-miehet-64-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-naiset-64-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-joista-helsinkilaisia-naiset-64-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-muut-64-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-joista-helsinkilaisia-muut-64-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-pojat-20-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-joista-helsinkilaisia-pojat-20-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-tytot-20-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-joista-helsinkilaisia-tytot-20-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-muut-20-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-joista-helsinkilaisia-muut-20-aktiiviharrastajat": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-valmentajien-ohjaajien-maara-edellisena-vuonna-yhteensa": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-joista-valmentaja-ja-ohjaajakoulutuksen-vok-1-5-tason-koulutukse": {
          role: 'number-input',
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        'edit-club-section': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-club-section-add-submit',
              resultValue: 'edit-club-section-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'select',
                  selector: {
                    type: 'by-label',
                    name: '',
                    value: 'edit-club-section-items-[INDEX]-item-sectionname',
                  },
                  value: 'Agility',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-men',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-women',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-adultothers',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-adulthours',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-seniormen',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-seniorwomen',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-seniorothers',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-seniorhours',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-boys',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-girls',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-juniorothers',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-juniorhours',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
              ],
              1: [
                {
                  role: 'select',
                  selector: {
                    type: 'by-label',
                    name: '',
                    value: 'edit-club-section-items-[INDEX]-item-sectionname',
                  },
                  value: 'Muu laji',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-sectionother',
                  },
                  value: faker.lorem.words(2),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-men',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-women',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-adultothers',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-adulthours',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-seniormen',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-seniorwomen',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-seniorothers',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-seniorhours',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-boys',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-girls',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-juniorothers',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector',
                    value: 'edit-club-section-items-[INDEX]-item-juniorhours',
                  },
                  value: faker.number.int({min: 12, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
              ],
            },
            expectedErrors: {}
          },
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
      },
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
          value: ATTACHMENTS.YHTEISON_SAANNOT,
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-vahvistettu-tilinpaatos-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilinpaatos_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilinpaatos-attachment a',
          },
          value: ATTACHMENTS.VAHVISTETTU_TILINPAATOS,
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-vahvistettu-toimintakertomus-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_toimintakertomus_attachment]"]',
            resultValue: '.form-item-vahvistettu-toimintakertomus-attachment a',
          },
          value: ATTACHMENTS.VAHVISTETTU_TOIMINTAKERTOMUS,
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilin_tai_toiminnantarkastuskertomus_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment a',
          },
          value: ATTACHMENTS.VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS,
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-toimintasuunnitelma-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[toimintasuunnitelma_attachment]"]',
            resultValue: '.form-item-toimintasuunnitelma-attachment a',
          },
          value: ATTACHMENTS.TOIMINTASUUNNITELMA,
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-talousarvio-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[talousarvio_attachment]"]',
            resultValue: '.form-item-talousarvio-attachment a',
          },
          value: ATTACHMENTS.TALOUSARVIO,
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-tilankayttoliite-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[tilankayttoliite_attachment]"]',
            resultValue: '.form-item-tilankayttoliite-attachment a',
          },
          value: ATTACHMENTS.TEST_PDF,
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-muu-liite-items-0-item-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[muu_liite_items_0__item__attachment]"]',
            resultValue: '.form-item-muu-liite-items-0--item--attachment a',
          },
          value: ATTACHMENTS.MUU_LIITE,
          viewPageSelector: '.form-item-muu-liite',
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-muu-liite-items-0-item-description': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-muu-liite-items-0-item-description',
          },
          viewPageSelector: '.form-item-muu-liite',
          value: faker.lorem.sentences(1),
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
          value: 'save-draft',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-draft',
          },
          viewPageSkipValidation: true,
        },
      },
    },
  },
  expectedErrors: {},
  expectedDestination: "/fi/hakemus/liikunta_toiminta_ja_tilankaytto/",
}

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values',
  viewPageSkipValidation: true,
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-bank-account-account-number-select',
        'edit-email',
        'edit-contact-person',
        'edit-contact-person-phone-number',
        'edit-community-address-community-address-select',
      ],
    },
    '2_avustustiedot': {
      items: {},
      itemsToRemove: [
        'edit-hakijan-tyyppi',
        'edit-acting-year',
        'edit-subventions-items-0-amount',
        'edit-subventions-items-1-amount',
        'edit-compensation-explanation',
        'edit-compensation-purpose',
      ],
    },
    '3_yhteison_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-miehet-20-63-vuotiaat-aktiiviharrastajat',
        'edit-joista-helsinkilaisia-miehet-20-63-aktiiviharrastajat',
        'edit-naiset-20-63-vuotiaat-aktiiviharrastajat',
        'edit-joista-helsinkilaisia-naiset-20-63-aktiiviharrastajat',
        'edit-muut-20-63-vuotiaat-aktiiviharrastajat',
        'edit-joista-helsinkilaisia-muut-20-63-aktiiviharrastajat',
        'edit-miehet-64-aktiiviharrastajat',
        'edit-joista-helsinkilaisia-miehet-64-aktiiviharrastajat',
        'edit-naiset-64-aktiiviharrastajat',
        'edit-joista-helsinkilaisia-naiset-64-aktiiviharrastajat',
        'edit-muut-64-aktiiviharrastajat',
        'edit-joista-helsinkilaisia-muut-64-aktiiviharrastajat',
        'edit-pojat-20-aktiiviharrastajat',
        'edit-joista-helsinkilaisia-pojat-20-aktiiviharrastajat',
        'edit-tytot-20-aktiiviharrastajat',
        'edit-joista-helsinkilaisia-tytot-20-aktiiviharrastajat',
        'edit-muut-20-aktiiviharrastajat',
        'edit-joista-helsinkilaisia-muut-20-aktiiviharrastajat',
        'edit-valmentajien-ohjaajien-maara-edellisena-vuonna-yhteensa',
        'edit-joista-valmentaja-ja-ohjaajakoulutuksen-vok-1-5-tason-koulutukse',
        'edit-club-section-items-0-item-sectionname',
        'edit-club-section-items-0-item-sectionother',
        'edit-club-section-items-0-item-men',
        'edit-club-section-items-0-item-women',
        'edit-club-section-items-0-item-adultothers',
        'edit-club-section-items-0-item-seniormen',
        'edit-club-section-items-0-item-seniorwomen',
        'edit-club-section-items-0-item-seniorothers',
        'edit-club-section-items-0-item-boys',
        'edit-club-section-items-0-item-girls',
        'edit-club-section-items-0-item-juniorothers',
      ],
    },
    'lisatiedot_ja_liitteet': {
      items: {},
      itemsToRemove: [
        'edit-yhteison-saannot-attachment-upload',
        'edit-vahvistettu-tilinpaatos-attachment-upload',
        'edit-vahvistettu-toimintakertomus-attachment-upload',
        'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload',
        'edit-toimintasuunnitelma-attachment-upload',
        'edit-talousarvio-attachment-upload',
        'edit-tilankayttoliite-attachment-upload'
      ],
    },
    'webform_preview': {
      items: {},
      itemsToRemove: [],
    },
  },
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite kenttä on pakollinen.',
    'edit-contact-person': 'Virhe sivulla 1. Hakijan tiedot: Yhteyshenkilö kenttä on pakollinen.',
    'edit-contact-person-phone-number': 'Virhe sivulla 1. Hakijan tiedot: Puhelinnumero kenttä on pakollinen.',
    'edit-community-address': 'Virhe sivulla 1. Hakijan tiedot: Yhteisön osoite kenttä on pakollinen.',
    'edit-community-address-community-address-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse osoite kenttä on pakollinen.',
    'edit-hakijan-tyyppi': 'Virhe sivulla 2. Avustustiedot: Hakijan tyyppi kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-compensation-purpose': 'Virhe sivulla 2. Avustustiedot: Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista kenttä on pakollinen.',
    'edit-compensation-explanation': 'Virhe sivulla 2. Avustustiedot: Selvitys avustuksen käytöstä kenttä on pakollinen.',
    'edit-miehet-20-63-vuotiaat-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Miehet (20-63-vuotiaat) kenttä on pakollinen.',
    'edit-joista-helsinkilaisia-miehet-20-63-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-naiset-20-63-vuotiaat-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Naiset (20-63-vuotiaat) kenttä on pakollinen.',
    'edit-joista-helsinkilaisia-naiset-20-63-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-muut-20-63-vuotiaat-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Muut (20-63-vuotiaat) kenttä on pakollinen.',
    'edit-joista-helsinkilaisia-muut-20-63-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-miehet-64-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Miehet (64 vuotta täyttäneet) kenttä on pakollinen.',
    'edit-joista-helsinkilaisia-miehet-64-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-naiset-64-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Naiset (64 vuotta täyttäneet) kenttä on pakollinen.',
    'edit-joista-helsinkilaisia-naiset-64-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-muut-64-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Muut (64 vuotta täyttäneet) kenttä on pakollinen.',
    'edit-joista-helsinkilaisia-muut-64-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-pojat-20-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Pojat (alle 20-vuotiaat) kenttä on pakollinen.',
    'edit-joista-helsinkilaisia-pojat-20-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-tytot-20-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Tytöt (alle 20-vuotiaat) kenttä on pakollinen.',
    'edit-joista-helsinkilaisia-tytot-20-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-muut-20-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Muut (alle 20-vuotiaat) kenttä on pakollinen.',
    'edit-joista-helsinkilaisia-muut-20-aktiiviharrastajat': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-valmentajien-ohjaajien-maara-edellisena-vuonna-yhteensa': 'Virhe sivulla 3. Yhteisön toiminta: Valmentajien/ohjaajien määrä edellisenä vuonna yhteensä kenttä on pakollinen.',
    'edit-joista-valmentaja-ja-ohjaajakoulutuksen-vok-1-5-tason-koulutukse': 'Virhe sivulla 3. Yhteisön toiminta: Joista valmentaja- ja ohjaajakoulutuksen (VOK) 1-5 tason koulutuksen suorittaneita on yhteensä kenttä on pakollinen.',
    'edit-club-section-items-0-item-sectionname': 'Virhe sivulla 3. Yhteisön toiminta: Laji kenttä on pakollinen.',
    'edit-club-section-items-0-item-men': 'Virhe sivulla 3. Yhteisön toiminta: Vähintään yksi ikäluokka on pakollinen.',
    'edit-club-section-items-0-item-women': 'Virhe sivulla 3. Yhteisön toiminta: Vähintään yksi ikäluokka on pakollinen.',
    'edit-club-section-items-0-item-adultothers': 'Virhe sivulla 3. Yhteisön toiminta: Vähintään yksi ikäluokka on pakollinen.',
    'edit-club-section-items-0-item-seniormen': 'Virhe sivulla 3. Yhteisön toiminta: Vähintään yksi ikäluokka on pakollinen.',
    'edit-club-section-items-0-item-seniorwomen': 'Virhe sivulla 3. Yhteisön toiminta: Vähintään yksi ikäluokka on pakollinen.',
    'edit-club-section-items-0-item-seniorothers': 'Virhe sivulla 3. Yhteisön toiminta: Vähintään yksi ikäluokka on pakollinen.',
    'edit-club-section-items-0-item-boys': 'Virhe sivulla 3. Yhteisön toiminta: Vähintään yksi ikäluokka on pakollinen.',
    'edit-club-section-items-0-item-girls': 'Virhe sivulla 3. Yhteisön toiminta: Vähintään yksi ikäluokka on pakollinen.',
    'edit-club-section-items-0-item-juniorothers': 'Virhe sivulla 3. Yhteisön toiminta: Vähintään yksi ikäluokka on pakollinen.',
    'edit-yhteison-saannot-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Yhteisön säännöt ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vahvistettu-tilinpaatos-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Vahvistettu tilinpäätös (edelliseltä päättyneeltä tilikaudelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vahvistettu-toimintakertomus-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Vahvistettu toimintakertomus (edelliseltä päättyneeltä tilikaudelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Vahvistettu tilin- tai toiminnantarkastuskertomus (edelliseltä päättyneeltä tilikaudelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-toimintasuunnitelma-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Toimintasuunnitelma (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-talousarvio-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Talousarvio (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
  },
};

const wrongValues: FormDataWithRemoveOptionalProps = {
  title: 'Wrong values',
  viewPageSkipValidation: true,
  formPages: {
    '1_hakijan_tiedot': {
      items: {
        "edit-email": {
          role: 'input',
          value: 'ääkkösiävaa',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-email',
          }
        },
      },
      itemsToRemove: [],
    },
    '2_avustustiedot': {
      items: {},
      itemsToRemove: [
        'edit-subventions-items-0-amount',
      ],
    },
    '3_yhteison_tiedot': {
      items: {},
    },
    'webform_preview': {
      items: {},
      itemsToRemove: [],
    },
  },
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite ääkkösiävaa ei kelpaa.',
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Myös "Toiminta-avustusta" on haettava, jos haetaan "Tilankäyttöavustusta".',
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
  expectedErrors: {},
};

const registeredCommunityApplications_60 = {
  draft: baseFormRegisteredCommunity_60,
  missing_values: createFormData(baseFormRegisteredCommunity_60, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_60, wrongValues),
  success: createFormData(baseFormRegisteredCommunity_60, sendApplication),
}

export {
  registeredCommunityApplications_60
}

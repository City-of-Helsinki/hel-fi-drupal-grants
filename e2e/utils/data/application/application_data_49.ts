import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker";
import {ATTACHMENTS} from "../attachment_data";
import {PROFILE_INPUT_DATA} from "../profile_input_data";
import {createFormData} from "../../form_data_helpers";
import {
  viewPageFormatCurrency,
  viewPageFormatBoolean,
  viewPageFormatDate,
  viewPageFormatFilePath,
  viewPageFormatAddress,
  viewPageFormatLowerCase,
  viewPageFormatNumber
} from "../../view_page_formatters";

const baseForm_49: FormData = {
  title: 'Save as draft.',
  formSelector: 'webform-submission-taide-ja-kulttuuri-kehittamisavu-form',
  formPath: '/fi/form/taide-ja-kulttuuri-kehittamisavu',
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
          viewPageFormatter: viewPageFormatLowerCase,
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
            name: '',
            value: '#edit-acting-year',
          },
          viewPageSkipValidation: true,
        },
        "edit-subventions-items-0-amount": {
          value: '5709,98',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-kyseessa-on-monivuotinen-avustus-1": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-kyseessa-on-monivuotinen-avustus-1',
          },
          value: "Kyllä",
        },
        "edit-vuodet-joille-monivuotista-avustusta-on-haettu-tai-myonetty": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-vuodet-joille-monivuotista-avustusta-on-haettu-tai-myonetty',
          },
          value: faker.lorem.words(5),
        },
        "edit-erittely-kullekin-vuodelle-haettavasta-avustussummasta": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-erittely-kullekin-vuodelle-haettavasta-avustussummasta',
          },
          value: faker.lorem.words(5),
        },
        "edit-ensisijainen-taiteen-ala": {
          role: 'select',
          value: 'Museo',
        },
        "edit-hankkeen-nimi": {
          value: faker.lorem.words(3).toLocaleUpperCase()
        },
        "edit-kyseessa-on-festivaali-tai-tapahtuma-1": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-kyseessa-on-festivaali-tai-tapahtuma-1',
          },
          value: "Kyllä",
        },
        "edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti',
          },
          value: faker.lorem.words(10),
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
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '3_yhteison_tiedot',
          },
          viewPageSkipValidation: true
        },
      },
    },
    '3_yhteison_tiedot': {
      items: {
        "edit-members-applicant-person-global": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-members-applicant-person-local": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-members-applicant-community-global": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-members-applicant-community-local": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-kokoaikainen-henkilosto": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-kokoaikainen-henkilotyovuosia": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-osa-aikainen-henkilosto": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-osa-aikainen-henkilotyovuosia": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-vapaaehtoinen-henkilosto": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '4_suunniteltu_toiminta',
          },
          viewPageSkipValidation: true,
        },
      }
    },
    "4_suunniteltu_toiminta": {
      items: {
        "edit-tila": {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-tila-add-submit',
              resultValue: 'edit-tila-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-tila-items-[INDEX]-item-premisename',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-tila-items-[INDEX]-item-postcode',
                  },
                  value: faker.location.zipCode(),
                },
                {
                  role: 'radio',
                  selector: {
                    type: 'partial-for-attribute',
                    name: '',
                    value: 'edit-tila-items-[INDEX]-item-isownedbycity-1',
                  },
                  value: "Kyllä",
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-tila-items-[INDEX]-item-premisename',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-tila-items-[INDEX]-item-postcode',
                  },
                  value: faker.location.zipCode(),
                },
                {
                  role: 'radio',
                  selector: {
                    type: 'partial-for-attribute',
                    name: '',
                    value: 'edit-tila-items-[INDEX]-item-isownedbycity-0',
                  },
                  value: "Ei",
                },
              ],
            },
            expectedErrors: {}
          },
        },
        "edit-festivaalin-tai-tapahtuman-paivamaarat": {
          role: 'input',
          value: faker.lorem.words(10),
        },
        "edit-hanke-alkaa": {
          role: 'input',
          value: "2023-11-01",
          viewPageFormatter: viewPageFormatDate
        },
        "edit-hanke-loppuu": {
          role: 'input',
          value: "2023-12-01",
          viewPageFormatter: viewPageFormatDate
        },
        "edit-laajempi-hankekuvaus": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '5_toiminnan_lahtokohdat',
          },
          viewPageSkipValidation: true,
        },
      },
    },
    "5_toiminnan_lahtokohdat": {
      items: {
        "edit-toiminta-taiteelliset-lahtokohdat": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-tasa-arvo": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-saavutettavuus": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-yhteisollisyys": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-kohderyhmat": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-ammattimaisuus": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-ekologisuus": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-yhteistyokumppanit": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '6_talous',
          },
          viewPageSkipValidation: true,
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
          viewPageSelector: '.form-item-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-',
          viewPageFormatter: viewPageFormatBoolean,
        },
        "edit-budget-static-income-plannedothercompensations": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-income',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-income-sponsorships": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-income',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-income-entryfees": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector-sequential',
            name: '',
            value: 'edit-budget-static-income-entryfees',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-income',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-income-sales": {
          role: 'input',
          selector: {
            type: 'data-drupal-selector-sequential',
            name: '',
            value: 'edit-budget-static-income-sales',
          },
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-income',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-income-ownfunding": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-income',
          viewPageFormatter: viewPageFormatCurrency,
        },
        'edit-budget-other-cost': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-budget-other-cost-add-submit',
              resultValue: 'edit-budget-other-cost-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-budget-other-cost-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-budget-other-cost',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-budget-other-cost-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-budget-other-cost',
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-budget-other-cost-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                  viewPageSelector: '.form-item-budget-other-cost',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-budget-other-cost-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                  viewPageSelector: '.form-item-budget-other-cost',
                },
              ],
            },
            expectedErrors: {}
          },
        },
        "edit-sisaltyyko-toiminnan-toteuttamiseen-jotain-muuta-rahanarvoista-p": {
          role: 'input',
          value: faker.lorem.sentences(3),
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
        'edit-projektisuunnitelma-liite-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[projektisuunnitelma_liite_attachment]"]',
            resultValue: '.form-item-projektisuunnitelma-liite-attachment a',
          },
          value: ATTACHMENTS.TOIMINTASUUNNITELMA,
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-talousarvio-liite-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[talousarvio_liite_attachment]"]',
            resultValue: '.form-item-talousarvio-liite-attachment a',
          },
          value: ATTACHMENTS.TALOUSARVIO,
          viewPageFormatter: viewPageFormatFilePath
        },
        "edit-muu-liite": {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-muu-liite-add-submit',
              resultValue: 'edit-muu-liite-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'fileupload',
                  selector: {
                    type: 'locator',
                    name: 'data-drupal-selector',
                    value: '[name="files[muu_liite_items_[INDEX]__item__attachment]"]',
                    resultValue: '.form-item-muu-liite-items-[INDEX]--item--attachment a',
                  },
                  value: ATTACHMENTS.MUU_LIITE,
                  viewPageFormatter: viewPageFormatFilePath
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-muu-liite-items-[INDEX]-item-description',
                  },
                  value: faker.lorem.sentences(1),
                },
              ],
            },
          },
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
          viewPageSkipValidation: true
        },
      },
    },
    "webform_preview": {
      items: {
        "accept_terms_1": {
          value: "1",
          viewPageSkipValidation: true
        },
        "sendbutton": {
          role: 'button',
          value: 'save-draft',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-draft',
          },
          viewPageSkipValidation: true
        },
      },
    },
  },
  expectedErrors: {},
  expectedDestination: "/fi/hakemus/taide_ja_kulttuuri_kehittamisavu/",
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
        'edit-community-address-community-address-select'
      ],
    },
    '2_avustustiedot': {
      items: {},
      itemsToRemove: [
        'edit-acting-year',
        'edit-subventions-items-0-amount',
        'edit-ensisijainen-taiteen-ala',
        'edit-hankkeen-nimi',
        'edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti',
        'edit-vuodet-joille-monivuotista-avustusta-on-haettu-tai-myonetty',
        'edit-erittely-kullekin-vuodelle-haettavasta-avustussummasta',
      ],
    },
    '4_suunniteltu_toiminta': {
      items: {},
      itemsToRemove: [
        'edit-tila-items-0-item-premisename',
        'edit-tila-items-0-item-postcode',
        'edit-tila-items-0-item-isownedbycity',
        'edit-hanke-alkaa',
        'edit-hanke-loppuu',
      ],
    },
    '6_talous': {
      items: {},
      itemsToRemove: [
        'edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-',
      ],
    },
    'lisatiedot_ja_liitteet': {
      items: {},
      itemsToRemove: [
        'edit-projektisuunnitelma-attachment-upload',
        'edit-talousarvio-attachment-upload',
      ],
    },
  },
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite kenttä on pakollinen.',
    'edit-contact-person': 'Virhe sivulla 1. Hakijan tiedot: Yhteyshenkilö kenttä on pakollinen.',
    'edit-contact-person-phone-number': 'Virhe sivulla 1. Hakijan tiedot: Puhelinnumero kenttä on pakollinen.',
    'edit-community-address': 'Virhe sivulla 1. Hakijan tiedot: Yhteisön osoite kenttä on pakollinen.',
    'edit-community-address-community-address-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse osoite kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-ensisijainen-taiteen-ala': 'Virhe sivulla 2. Avustustiedot: Ensisijainen taiteenala kenttä on pakollinen.',
    'edit-hankkeen-nimi': 'Virhe sivulla 2. Avustustiedot: Hankkeen nimi kenttä on pakollinen.',
    'edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti': 'Virhe sivulla 2. Avustustiedot: Hankkeen tai toiminnan lyhyt esittelyteksti kenttä on pakollinen.',
    'edit-vuodet-joille-monivuotista-avustusta-on-haettu-tai-myonetty': 'Virhe sivulla 2. Avustustiedot: Tulevat vuodet, joiden ajalle monivuotista avustusta haetaan tai on myönnetty kenttä on pakollinen.',
    'edit-erittely-kullekin-vuodelle-haettavasta-avustussummasta': 'Virhe sivulla 2. Erittely kullekin vuodelle haettavasta avustussummasta kenttä on pakollinen.',
    'edit-tila-items-0-item-premisename': 'Virhe sivulla 4. Suunniteltu toiminta: Tilan nimi kenttä on pakollinen.',
    'edit-tila-items-0-item-postcode': 'Virhe sivulla 4. Suunniteltu toiminta: Postinumero kenttä on pakollinen.',
    'edit-tila-items-0-item-isownedbycity': 'Virhe sivulla 4. Suunniteltu toiminta: Kyseessä on kaupungin omistama tila kenttä on pakollinen.',
    'edit-hanke-alkaa': 'Virhe sivulla 4. Suunniteltu toiminta: Hanke alkaa kenttä on pakollinen.',
    'edit-hanke-loppuu': 'Virhe sivulla 4. Suunniteltu toiminta: Hanke loppuu kenttä on pakollinen.',
    'edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-': 'Virhe sivulla 6. Talous: Organisaatio kuuluu valtionosuusjärjestelmään (VOS) kenttä on pakollinen.',
    'edit-projektisuunnitelma-attachment-upload': 'Virhe sivulla 7. Lisätiedot ja liitteet: Projektisuunnitelma ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-talousarvio-attachment-upload': 'Virhe sivulla 7. Lisätiedot ja liitteet: Talousarvio ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
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
  },
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: ääkkösiävaa ei ole kelvollinen sähköpostiosoite. Täytä sähköpostiosoite muodossa user@example.com.',
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
          },
          viewPageSkipValidation: true,
        },
      },
      itemsToRemove: [],
    },
  },
  expectedErrors: {},
};

const registeredCommunityApplications_49 = {
  draft: baseForm_49,
  missing_values: createFormData(baseForm_49, missingValues),
  wrong_values: createFormData(baseForm_49, wrongValues),
  success: createFormData(baseForm_49, sendApplication),
}

export {
  registeredCommunityApplications_49
}

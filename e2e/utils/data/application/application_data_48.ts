import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PATH_MUU_LIITE} from "../../helpers";
import {PROFILE_INPUT_DATA} from "../profile_input_data";
import {createFormData} from "../../form_helpers";
import {
  viewPageFormatCurrency,
  viewPageFormatBoolean,
  viewPageFormatDate,
  viewPageFormatFilePath,
  viewPageFormatAddress,
  viewPageFormatLowerCase, viewPageFormatNumber
} from "../../view_page_formatters";

/**
 * Basic form data for successful submit to Avus2. This object contains ALL
 * fields with proper data for REGISTERED COMMUNITY. Then you override this for
 * private persons & unregistered community for 1st page at least.
 *
 * And then when you want to test out different options for any given group, you
 * just override suitable object and use that for running tests.
 *
 */
const baseForm_48: FormData = {
  title: 'Form submit to avus2',
  formSelector: 'webform-submission-kuva-projekti-form',
  formPath: '/fi/form/kuva-projekti',
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
        "edit-acting-year": {
          value: '2024',
        },
        "edit-subventions-items-0-amount": {
          value: '5709,98',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatCurrency
        },
        "edit-ensisijainen-taiteen-ala": {
          role: 'select',
          value: 'Museo',
        },
        "edit-hankkeen-nimi": {
          value: faker.lorem.words(3).toLocaleUpperCase()
        },
        "edit-kyseessa-on-festivaali-tai-tapahtuma-0": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-kyseessa-on-festivaali-tai-tapahtuma-1',
          },
          value: "0",
          viewPageFormatter: viewPageFormatBoolean
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
              // @ts-ignore
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
                // so far the multivalue fields are not working as expected, and these are in need of fixing.
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
          },
          viewPageSkipValidation: true,
        },
      },
    },
    '3_yhteison_tiedot': {
      items: {
        "edit-members-applicant-person-global": {
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-members-applicant-person-local": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-members-applicant-community-global": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-members-applicant-community-local": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-kokoaikainen-henkilosto": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-kokoaikainen-henkilotyovuosia": {
          role: 'input',
          value: faker.number.float({
            min: 1,
            max: 100,
            precision: 2
          }).toString(),
        },
        "edit-osa-aikainen-henkilosto": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-osa-aikainen-henkilotyovuosia": {
          role: 'input',
          value: faker.number.float({
            min: 1,
            max: 100,
            precision: 2
          }).toString(),
        },
        "edit-vapaaehtoinen-henkilosto": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
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
      },
    },
    "4_suunniteltu_toiminta": {
      items: {
        "edit-tapahtuma-tai-esityspaivien-maara-helsingissa": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-esitykset-maara-helsingissa": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-tyopaja-maara-helsingissa": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-nayttelyt-maara-helsingissa": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-esitykset-maara-kaikkiaan": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-nayttelyt-maara-kaikkiaan": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-tyopaja-maara-kaikkiaan": {
          role: 'input',
          value: faker.number.int({min: 1, max: 100}).toString(),
        },
        "edit-maara-helsingissa": {
          role: 'input',
          value: faker.number.int({min: 100, max: 10000}).toString(),
        },
        "edit-maara-kaikkiaan": {
          role: 'input',
          value: faker.number.int({min: 1000, max: 100000}).toString(),
        },
        "edit-kantaesitysten-maara": {
          role: 'input',
          value: faker.number.int({min: 10, max: 100}).toString(),
        },
        "edit-ensi-iltojen-maara-helsingissa": {
          role: 'input',
          value: faker.number.int({min: 10, max: 100}).toString(),
        },
        "edit-ensimmainen-yleisolle-avoimen-tilaisuuden-paikka-helsingissa": {
          role: 'input',
          value: faker.company.buzzPhrase(),
        },
        "edit-postinumero": {
          role: 'input',
          value: faker.number.int({min: 10000, max: 99999}).toString(),
        },
        "edit-kyseessa-on-kaupungin-omistama-tila-1": {
          role: 'radio',
          value: "0",
          viewPageFormatter: viewPageFormatBoolean
        },
        "edit-ensimmaisen-yleisolle-avoimen-tilaisuuden-paivamaara": {
          role: 'input',
          value: "2023-11-01",
          viewPageFormatter: viewPageFormatDate
        },
        "edit-festivaalin-tai-tapahtuman-kohdalla-tapahtuman-paivamaarat": {
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
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-income',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-income-sales": {
          role: 'input',
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
        "edit-budget-static-cost-personnelsidecosts": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-cost',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-cost-performerfees": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-cost',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-cost-otherfees": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-cost',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-cost-showcosts": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-cost',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-cost-travelcosts": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-cost',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-cost-transportcosts": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-cost',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-cost-equipment": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-cost',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-cost-premises": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-cost',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-static-cost-marketing": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-cost',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-budget-other-cost-items-0-item-label": {
          role: 'input',
          value: faker.lorem.sentence(15),
          viewPageSelector: '.form-item-budget-other-cost',
        },
        "edit-budget-other-cost-items-0-item-value": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-other-cost',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-muu-huomioitava-panostus": {
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
          role: 'input',
          value: faker.lorem.sentences(3),
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
          viewPageSelector: '.form-item-muu-liite',
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-muu-liite-items-0-item-description': {
          role: 'input',
          value: faker.lorem.sentences(1),
          viewPageSelector: '.form-item-muu-liite'
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
          viewPageSkipValidation: true,
        },
      },
    },
    "webform_preview": {
      items: {
        "accept_terms_1": {
          role: 'checkbox',
          // selector: {
          //   type: 'label',
          //   name: 'Label',
          //   details: {
          //     label: 'Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot',
          //     options: {
          //       exact: true
          //     }
          //   },
          // },
          value: "1",
          viewPageSkipValidation: true,
        },
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
    },
  },
  expectedErrors: {},
  expectedDestination: "/fi/hakemus/kuva_projekti/",
}


/**
 * Basic form data for successful submit to Avus2.
 *
 * Private person.
 */
const baseFormPrivatePerson_48: FormData = createFormData(
  baseForm_48,
  {
    formPages: {
      "1_hakijan_tiedot": {
        items: {
          "edit-bank-account-account-number-select": {
            role: 'select',
            value: PROFILE_INPUT_DATA.iban,
            viewPageSelector: '.form-item-bank-account',
          },
          "edit-community-officials-items-0-item-community-officials-select": {
            viewPageSkipValidation: true,
          },
          "edit-email": {
            viewPageSkipValidation: true,
          },
          "edit-contact-person": {
            viewPageSkipValidation: true,
          },
          "edit-contact-person-phone-number": {
            viewPageSkipValidation: true,
          },
          "edit-community-address-community-address-select": {
            viewPageSkipValidation: true,
          },
        },
      },
    },
  }
);


/**
 * Basic form data for successful submit to Avus2.
 *
 * Unregistered community.
 */
const baseFormUnRegisteredCommunity_48: FormData = createFormData(
  baseForm_48,
  {
    formPages: {
      "1_hakijan_tiedot": {
        items: {
          "edit-bank-account-account-number-select": {
            role: 'select',
            value: PROFILE_INPUT_DATA.iban,
            viewPageSelector: '.form-item-bank-account',
          },
          "edit-community-officials-items-0-item-community-officials-select": {
            role: 'select',
            value: PROFILE_INPUT_DATA.communityOfficial,
            viewPageSelector: '.form-item-community-officials',
          },
          "edit-email": {
           viewPageSkipValidation: true,
          },
          "edit-contact-person": {
            viewPageSkipValidation: true,
          },
          "edit-contact-person-phone-number": {
            viewPageSkipValidation: true,
          },
          "edit-community-address-community-address-select": {
            viewPageSkipValidation: true,
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
    },
  }
);

/**
 * Overridden form to remove some values.
 */
const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values from 1st page',
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: ['edit-bank-account-account-number-select'],
    },
    'webform_preview': {
      items: {
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
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
  },
};


/**
 * Overridden form to save as a DRAFT
 */
const saveDraft: FormDataWithRemoveOptionalProps = {
  title: 'Save to draft and verify data',
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
          },
          viewPageSkipValidation: true,
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {},
};

/**
 * All data for registered community, keyed with id. Those do not matter.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 *
 */
const registeredCommunityApplications_48 = {
  // success: baseForm_48,
  draft: createFormData(baseForm_48, saveDraft),
  // missing_values: createFormData(baseForm_48, missingValues),
}

/**
 * All data for private persons' applications.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 */
const privatePersonApplications_48 = {
  // success: baseFormPrivatePerson_48,
  draft: createFormData(baseFormPrivatePerson_48, saveDraft),
  // missing_values: createFormData(baseFormPrivatePerson_48, missingValues),
}

/**
 * All data for unregistered community applications.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 */
const unRegisteredCommunityApplications_48 = {
  // success: baseFormUnRegisteredCommunity_48,
  draft: createFormData(baseFormUnRegisteredCommunity_48, saveDraft),
  // missing_values: createFormData(baseFormUnRegisteredCommunity_48, missingValues),
}

export {
  privatePersonApplications_48,
  registeredCommunityApplications_48,
  unRegisteredCommunityApplications_48
}

import {FormData, FormDataWithRemoveOptionalProps} from '../test_data';
import {fakerFI as faker} from '@faker-js/faker'
import {PROFILE_INPUT_DATA} from '../profile_input_data';
import {ATTACHMENTS} from '../attachment_data';
import {createFormData} from '../../form_data_helpers';
import {
  viewPageFormatAddress,
  viewPageFormatBoolean,
  viewPageFormatCurrency,
  viewPageFormatDate,
  viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatNumber
} from '../../view_page_formatters';
import {getFakeEmailAddress} from '../../field_helpers';

/**
 * Basic form data for successful save as a draft
 */
const baseForm_70: FormData = {
  title: 'Save as draft.',
  formSelector: 'webform-submission-iakkaiden-kulttuuri-ja-liikunta-form',
  formPath: '/fi/form/iakkaiden-kulttuuri-ja-liikunta',
  formPages: {
    '1_hakijan_tiedot': {
      items: {
        'edit-email': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-email',
          },
          value: getFakeEmailAddress(),
          viewPageFormatter: viewPageFormatLowerCase,
        },
        'edit-contact-person': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-contact-person',
          },
          value: faker.person.fullName(),
        },
        'edit-contact-person-phone-number': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-contact-person-phone-number',
          },
          value: faker.phone.number(),
        },
        'edit-bank-account-account-number-select': {
          role: 'select',
          value: PROFILE_INPUT_DATA.iban,
          viewPageSelector: '.form-item-bank-account',
        },
        'edit-community-address-community-address-select': {
          value: `${PROFILE_INPUT_DATA.address}, ${PROFILE_INPUT_DATA.zipCode}, ${PROFILE_INPUT_DATA.city}`,
          viewPageSelector: '.form-item-community-address',
          viewPageFormatter: viewPageFormatAddress
        },
        'edit-community-officials-items-0-item-community-officials-select': {
          role: 'select',
          viewPageSelector: '.form-item-community-officials',
          value: PROFILE_INPUT_DATA.communityOfficial,
        },
        'nextbutton': {
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
    '2_avustustiedot': {
      items: {
        'edit-acting-year': {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: '',
            value: '#edit-acting-year',
          },
          viewPageSkipValidation: true,
        },
        'edit-subventions-items-0-amount': {
          role: 'number-input',
          value: '5709,98',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatCurrency
        },
        'edit-compensation-purpose': {
          value: faker.lorem.sentences(4),
        },
        'edit-myonnetty-avustus': {
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
                      multipleOf: 2
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
                      multipleOf: 2
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
        'edit-haettu-avustus-tieto': {
          role: 'dynamicmultivalue',
          label: '',
          dynamic_multi: {
            radioSelector: {
              type: 'dom-id-label',
              name: 'data-drupal-selector',
              value: 'edit-olemme-hakeneet-avustuksia-muualta-kuin-helsingin-kaupungilta-1',
            },
            revealedElementSelector: {
              type: 'dom-id',
              name: '',
              value: '#edit-haettu-avustus-tieto',
            },
            multi: {
              buttonSelector: {
                type: 'data-drupal-selector',
                name: 'data-drupal-selector',
                value: 'edit-haettu-avustus-tieto-add-submit',
                resultValue: 'edit-haettu-avustus-tieto-items-[INDEX]',
              },
              //@ts-ignore
              items: {
                0: [
                  {
                    role: 'select',
                    selector: {
                      type: 'by-label',
                      name: '',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-issuer',
                    },
                    value: 'Muu',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-issuer-name',
                    },
                    value: faker.lorem.words(2).toUpperCase(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-year',
                    },
                    value: faker.date.past().getFullYear().toString(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector-sequential',
                      name: 'data-drupal-selector-sequential',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-amount',
                    },
                    value: faker.number.float({
                      min: 1000,
                      max: 10000,
                      multipleOf: 2
                    }).toString(),
                    viewPageFormatter: viewPageFormatCurrency,
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-purpose',
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
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-issuer',
                    },
                    value: 'Säätiö',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-issuer-name',
                    },
                    value: faker.lorem.words(2).toUpperCase(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-year',
                    },
                    value: faker.date.past().getFullYear().toString(),
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector-sequential',
                      name: 'data-drupal-selector-sequential',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-amount',
                    },
                    value: faker.number.float({
                      min: 1000,
                      max: 10000,
                      multipleOf: 2
                    }).toString(),
                    viewPageFormatter: viewPageFormatCurrency,
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-haettu-avustus-tieto-items-[INDEX]-item-purpose',
                    },
                    value: faker.lorem.words(30),
                  },
                ],
              },
              expectedErrors: {}
            }
          },
        },
        'nextbutton': {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '3_tarkemmat_tiedot',
          }
        },
      },
    },
    '3_tarkemmat_tiedot': {
      items: {
        'edit-hankesuunnitelma-radios-0': {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-hankesuunnitelma-radios-0',
          },
          value: 'Ei',
          viewPageFormatter: viewPageFormatBoolean
        },
        'edit-ensisijainen-taiteen-ala': {
          role: 'select',
          viewPageSelector: '.form-item-ensisijainen-taiteen-ala',
          value: 'Kirjallisuus',
        },
        // Section 3.1.
        'edit-hankesuunnitelma-jatkohakemus': {
          role: 'radio',
          value: '0',
          viewPageFormatter: viewPageFormatBoolean
        },
        'edit-hankkeen-tarkoitus-tavoitteet': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-tarkoitus-tavoitteet',
          },
          value: faker.lorem.words(10),
        },
        'edit-hankkeen-toimenpiteet-aikataulu': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-toimenpiteet-aikataulu',
          },
          value: faker.lorem.words(10),
        },
        'edit-hankkeen-toimenpiteet-alkupvm': {
          role: 'input',
          value: '2024-11-14',
          viewPageFormatter: viewPageFormatDate,
          viewPageSelector: '#iakkaiden_kulttuuri_ja_liikunta--hankesuunnitelma_section',
        },
        'edit-hankkeen-toimenpiteet-loppupvm': {
          role: 'input',
          value: '2025-11-14',
          viewPageFormatter: viewPageFormatDate,
          viewPageSelector: '#iakkaiden_kulttuuri_ja_liikunta--hankesuunnitelma_section',
        },
        'edit-hankkeen-keskeisimmat-kumppanit': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-keskeisimmat-kumppanit',
          },
          value: faker.lorem.words(10),
        },
        // Section 3.2.
        'edit-haun-painopisteet-liikkumis-kehitys': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-haun-painopisteet-liikkumis-kehitys',
          },
          value: faker.lorem.words(10),
        },
        'edit-haun-painopisteet-digi-kehitys': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-haun-painopisteet-digi-kehitys',
          },
          value: faker.lorem.words(10),
        },
        'edit-haun-painopisteet-vertais-kehitys': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-haun-painopisteet-vertais-kehitys',
          },
          value: faker.lorem.words(10),
        },
        'edit-haun-painopisteet-kulttuuri-kehitys': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-haun-painopisteet-kulttuuri-kehitys',
          },
          value: faker.lorem.words(10),
        },
        // Section 3.3.
        'edit-hankkeen-kohderyhmat-kenelle': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-kohderyhmat-kenelle',
          },
          value: faker.lorem.words(10),
        },
        'edit-hankkeen-kohderyhmat-erityisryhmat': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-kohderyhmat-erityisryhmat',
          },
          value: faker.lorem.words(10),
        },
        'edit-hankkeen-kohderyhmat-tavoitus': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-kohderyhmat-tavoitus',
          },
          value: faker.lorem.words(10),
        },
        'edit-hankkeen-kohderyhmat-konkretia': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-kohderyhmat-konkretia',
          },
          value: faker.lorem.words(10),
        },
        'edit-hankkeen-kohderyhmat-osallisuus': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-kohderyhmat-osallisuus',
          },
          value: faker.lorem.words(10),
        },
        'edit-hankkeen-kohderyhmat-osaaminen': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-kohderyhmat-osaaminen',
          },
          value: faker.lorem.words(10),
        },
        'edit-hankkeen-kohderyhmat-postinrot': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-kohderyhmat-postinrot',
          },
          value: faker.location.zipCode(),
        },
        'edit-hankkeen-kohderyhmat-miksi-alue': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-kohderyhmat-miksi-alue',
          },
          value: faker.lorem.words(10),
        },
        // Section 3.4.
        'edit-hankkeen-riskit-keskeisimmat': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-riskit-keskeisimmat',
          },
          value: faker.lorem.words(10),
        },
        'edit-hankkeen-riskit-seuranta': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-riskit-seuranta',
          },
          value: faker.lorem.words(10),
        },
        'edit-hankkeen-riskit-vakiinnuttaminen': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-hankkeen-riskit-vakiinnuttaminen',
          },
          value: faker.lorem.words(10),
        },
        'nextbutton': {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '4_talousarvio',
          },
        },
      },
    },
    '4_talousarvio': {
      items: {
        'edit-talous-tulon-tyyppi': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-talous-tulon-tyyppi-add-submit',
              resultValue: 'edit-talous-tulon-tyyppi-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-talous-tulon-tyyppi-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(2),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-talous-tulon-tyyppi-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-talous-tulon-tyyppi-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(2),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-talous-tulon-tyyppi-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
              ],
            },
            expectedErrors: {}
          },
        },
        'edit-talous-menon-tyyppi': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-talous-menon-tyyppi-add-submit',
              resultValue: 'edit-talous-menon-tyyppi-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-talous-menon-tyyppi-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(2),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-talous-menon-tyyppi-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-talous-menon-tyyppi-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(2),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-talous-menon-tyyppi-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
              ],
            },
            expectedErrors: {}
          },
        },
        'nextbutton': {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'lisatiedot_ja_liitteet',
          }
        },
      },
    },
    'lisatiedot_ja_liitteet': {
      items: {
        'edit-additional-information': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-additional-information',
          },
          value: faker.lorem.sentences(3),
        },
        'edit-extra-info': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-extra-info',
          },
          value: faker.lorem.sentences(3),
        },
        'edit-muu-liite': {
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
        'nextbutton': {
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
    'webform_preview': {
      items: {
        'accept_terms_1': {
          role: 'checkbox',
          value: '1',
          viewPageSkipValidation: true,
        },
        'sendbutton': {
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
  expectedDestination: '/fi/hakemus/iakkaiden_kulttuuri_ja_liikunta/',
}

/**
 * Test the visibility of Section 3. fields which are controlled by the
 * Section 2 "Kulttuurin erityisavustus 1" subventions value.
 */
const visibilityByState: FormDataWithRemoveOptionalProps = {
  title: 'Kulttuuri1_summa is 0',
  viewPageSkipValidation: true,
  formPages: {
    '2_avustustiedot': {
      items: {
        'edit-subventions-items-1-amount': {
          role: 'number-input',
          value: '5000',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatCurrency
        },
      },
      itemsToRemove: [
        'edit-subventions-items-0-amount',
        'edit-bank-account-account-number-select',
      ],
    },
    '3_tarkemmat_tiedot': {
      items: {
        'edit-hankesuunnitelma-radios-0': {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-hankesuunnitelma-radios-0',
          },
          value: 'Ei',
          viewPageFormatter: viewPageFormatBoolean
        },

      },
      itemsToBeHidden: [
        'edit-ensisijainen-taiteen-ala',
        'edit-haun-painopisteet-kulttuuri-kehitys',
      ],
    },
  },
  expectedErrors: {},
};

/**
 * Test the visibility of Section 3.5. This section is visible only when
 * "Haetaanko nyt vuonna 2024 myönnetyn kaksivuotisen avustuksen 2. osaa?"
 * form field (edit-hankesuunnitelma-radios) is set to "Kyllä".
 */
const visibilityByStateSection3Estimates: FormDataWithRemoveOptionalProps = {
  title: 'Section 3.5 test hidden values',
  viewPageSkipValidation: true,
  formPages: {
    '2_avustustiedot': {
      items: {
        'edit-subventions-items-1-amount': {
          role: 'number-input',
          value: '5000',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatCurrency
        },
      },
      itemsToRemove: [
        'edit-subventions-items-0-amount',
        'edit-bank-account-account-number-select',
      ],
    },
    '3_tarkemmat_tiedot': {
      items: {
        'edit-hankesuunnitelma-radios-1': {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-hankesuunnitelma-radios-1',
          },
          value: 'Kyllä',
          viewPageFormatter: viewPageFormatBoolean
        },

        // Section 3.5.
        'edit-arviointi-toteuma': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-arviointi-toteuma',
          },
          value: faker.lorem.words(10),
        },
        'edit-arviointi-muutokset-talous': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-arviointi-muutokset-talous',
          },
          value: faker.lorem.words(10),
        },
        'edit-arviointi-muutokset-toiminta': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-arviointi-muutokset-toiminta',
          },
          value: faker.lorem.words(10),
        },
        'edit-arviointi-muutokset-aikataulu': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-arviointi-muutokset-aikataulu',
          },
          value: faker.lorem.words(10),
        },
        'edit-arviointi-haasteet': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-arviointi-haasteet',
          },
          value: faker.lorem.words(10),
        },
        'edit-arviointi-saavutettavuus': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-arviointi-saavutettavuus',
          },
          value: faker.lorem.words(10),
        },
        'edit-arviointi-avustus-kaytto': {
          role: 'input',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-arviointi-avustus-kaytto',
          },
          value: faker.lorem.words(10),
        },
      },
      itemsToRemove: [
        'edit-hankesuunnitelma-radios-0',
        'edit-hankkeen-toimenpiteet-alkupvm',
        'edit-ensisijainen-taiteen-ala',
      ],
      itemsToBeHidden: [
        'edit-hankesuunnitelma-jatkohakemus',
        'edit-hankkeen-tarkoitus-tavoitteet',
        'edit-hankkeen-toimenpiteet-aikataulu',
        'edit-hankkeen-toimenpiteet-alkupvm',
        'edit-hankkeen-toimenpiteet-loppupvm',
        'edit-hankkeen-keskeisimmat-kumppanit',
        'edit-haun-painopisteet-liikkumis-kehitys',
        'edit-haun-painopisteet-digi-kehitys',
        'edit-haun-painopisteet-vertais-kehitys',
        'edit-haun-painopisteet-kulttuuri-kehitys',
        'edit-hankkeen-kohderyhmat-kenelle',
        'edit-hankkeen-kohderyhmat-erityisryhmat',
        'edit-hankkeen-kohderyhmat-tavoitus',
        'edit-hankkeen-kohderyhmat-konkretia',
        'edit-hankkeen-kohderyhmat-osallisuus',
        'edit-hankkeen-kohderyhmat-osaaminen',
        'edit-hankkeen-kohderyhmat-postinrot',
        'edit-hankkeen-kohderyhmat-miksi-alue',
        'edit-hankkeen-riskit-keskeisimmat',
        'edit-hankkeen-riskit-seuranta',
        'edit-hankkeen-riskit-vakiinnuttaminen',
      ],
    },
  },
  expectedErrors: {},
};

/**
 * Test the missing values of the whole form.
 */
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
        'edit-acting-year',
        'edit-subventions-items-0-amount',
        'edit-myonnetty-avustus',
        'edit-haettu-avustus-tieto',
      ],
    },
    '3_tarkemmat_tiedot': {
      items: {
        'edit-hankesuunnitelma-radios-0': {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-hankesuunnitelma-radios-0',
          },
          value: 'Ei',
          viewPageFormatter: viewPageFormatBoolean
        },
      },
      itemsToRemove: [
        'edit-ensisijainen-taiteen-ala',
        'edit-hankkeen-tarkoitus-tavoitteet',
        'edit-hankkeen-toimenpiteet-aikataulu',
        'edit-hankkeen-toimenpiteet-alkupvm',
        'edit-hankkeen-toimenpiteet-loppupvm',
        'edit-hankkeen-keskeisimmat-kumppanit',
        'edit-haun-painopisteet-liikkumis-kehitys',
        'edit-haun-painopisteet-digi-kehitys',
        'edit-haun-painopisteet-vertais-kehitys',
        'edit-haun-painopisteet-kulttuuri-kehitys',
        'edit-hankkeen-kohderyhmat-kenelle',
        'edit-hankkeen-kohderyhmat-tavoitus',
        'edit-hankkeen-kohderyhmat-konkretia',
        'edit-hankkeen-kohderyhmat-osallisuus',
        'edit-hankkeen-kohderyhmat-osaaminen',
        'edit-hankkeen-kohderyhmat-postinrot',
        'edit-hankkeen-kohderyhmat-miksi-alue',
        'edit-hankkeen-riskit-keskeisimmat',
        'edit-hankkeen-riskit-seuranta',
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
    'edit-hankkeen-tarkoitus-tavoitteet': 'Virhe sivulla 3. Tarkemmat tiedot: Hankkeen tarkoitus ja tavoitteet kenttä on pakollinen.',
    'edit-hankkeen-toimenpiteet-aikataulu': 'Virhe sivulla 3. Tarkemmat tiedot: Mitkä ovat hankkeen konkreettiset toimenpiteet ja niiden toteutusaikataulu? kenttä on pakollinen.',
    'edit-hankkeen-toimenpiteet-alkupvm': 'Virhe sivulla 3. Tarkemmat tiedot: Alkupäivämäärä kenttä on pakollinen.',
    'edit-hankkeen-toimenpiteet-loppupvm': 'Virhe sivulla 3. Tarkemmat tiedot: Loppupäivämäärä kenttä on pakollinen.',
    'edit-hankkeen-keskeisimmat-kumppanit': 'Virhe sivulla 3. Tarkemmat tiedot: Nimeä hankkeen keskeisimmät yhteistyökumppanit ja heidän roolinsa hankkeessa kenttä on pakollinen.',
    'edit-haun-painopisteet-digi-kehitys': 'Virhe sivulla 3. Tarkemmat tiedot: Kehitetäänkö hankkeessa digitaalisesti / etänä toteutettavia kulttuuritoimintoja tai liikkumiseen aktivoivaa toimintaa? Miten? kenttä on pakollinen.',
    'edit-haun-painopisteet-vertais-kehitys': 'Virhe sivulla 3. Tarkemmat tiedot: Kehitetäänkö hankkeessa vapaaehtois- / vertaistoimintaa? Miten? kenttä on pakollinen.',
    'edit-hankkeen-kohderyhmat-kenelle': 'Virhe sivulla 3. Tarkemmat tiedot: Kenelle hankkeen toiminta on pääasiallisesti suunnattu? kenttä on pakollinen.',
    'edit-hankkeen-kohderyhmat-tavoitus': 'Virhe sivulla 3. Tarkemmat tiedot: Kuinka hankkeen kohderyhmät aiotaan tavoittaa? kenttä on pakollinen.',
    'edit-hankkeen-kohderyhmat-konkretia': 'Virhe sivulla 3. Tarkemmat tiedot: Miten hankkeessa edistetään konkreettisin toimenpitein valitun kohderyhmän toimintakykyä ja hyvinvointia? kenttä on pakollinen.',
    'edit-hankkeen-kohderyhmat-osallisuus': 'Virhe sivulla 3. Tarkemmat tiedot: Millä tavoin hankkeessa edistetään osallisuutta? Mikä ikäihmisten rooli hankkeessa on? kenttä on pakollinen.',
    'edit-hankkeen-kohderyhmat-osaaminen': 'Virhe sivulla 3. Tarkemmat tiedot: Millaista osaamista kyseisen kohderyhmän/-ryhmien kanssa työskentelystä hanketoimijoilla on ennestään? kenttä on pakollinen.',
    'edit-hankkeen-kohderyhmat-postinrot': 'Virhe sivulla 3. Tarkemmat tiedot: Millä postinumeroalueella tai -alueilla Helsingissä hanke toteutetaan? kenttä on pakollinen.',
    'edit-hankkeen-kohderyhmat-miksi-alue': 'Virhe sivulla 3. Tarkemmat tiedot: Miksi juuri kyseinen alue / alueet on valittu? kenttä on pakollinen.',
    'edit-hankkeen-riskit-keskeisimmat': 'Virhe sivulla 3. Tarkemmat tiedot: Mitkä ovat hankkeen toteuttamisen näkökulmasta keskeisimmät riskit? kenttä on pakollinen.',
    'edit-hankkeen-riskit-seuranta': 'Virhe sivulla 3. Tarkemmat tiedot: Miten hankkeessa aiotaan toteuttaa seurantaa ja arviointia? kenttä on pakollinen.',
  },
};

/**
 * Test the wrong values for the whole form.
 */
const wrongValues: FormDataWithRemoveOptionalProps = {
  title: 'Wrong values',
  viewPageSkipValidation: true,
  formPages: {
    '1_hakijan_tiedot': {
      items: {
        'edit-email': {
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
    '4_talousarvio': {
      items: {},
      itemsToRemove: [
        'edit-talous-tulon-tyyppi-items-0-item-label',
        'edit-talous-menon-tyyppi-items-0-item-value'
      ],
    },
  },
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: ääkkösiävaa ei ole kelvollinen sähköpostiosoite. Täytä sähköpostiosoite muodossa user@example.com.',
    'edit-talous-tulon-tyyppi-items-0-item-label': 'Virhe sivulla 4. Talousarvio: Kuvaus tulosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
    'edit-talous-menon-tyyppi-items-0-item-value': 'Virhe sivulla 4. Talousarvio: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon'
  },
};

const sendApplication: FormDataWithRemoveOptionalProps = {
  title: 'Send to AVUS2',
  formPages: {
    'webform_preview': {
      items: {
        'sendbutton': {
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

/**
 * All data for registered community, keyed with id. Those do not matter.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 *
 */
const registeredCommunityApplications_70 = {
  draft: baseForm_70,
  visibilityByState: createFormData(baseForm_70, visibilityByState),
  visibilityByStateSection3Estimates: createFormData(baseForm_70, visibilityByStateSection3Estimates),
  missingValues: createFormData(baseForm_70, missingValues),
  wrongValues: createFormData(baseForm_70, wrongValues),
  success: createFormData(baseForm_70, sendApplication),
}

export {
  registeredCommunityApplications_70
}

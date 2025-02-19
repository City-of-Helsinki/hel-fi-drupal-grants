import { faker } from '@faker-js/faker';
import { getFakeEmailAddress } from '../../field_helpers';
import { viewPageFormatAddress, viewPageFormatBoolean, viewPageFormatCurrency, viewPageFormatFilePath, viewPageFormatLowerCase } from '../../view_page_formatters';
import { FormData, FormDataWithRemoveOptionalProps, FormPage } from '../test_data';
import { PROFILE_INPUT_DATA } from '../profile_input_data';
import { createFormData } from '../../form_data_helpers';
import { ATTACHMENTS } from '../attachment_data';

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_50: FormData = {
  title: 'Save as draft.',
  formSelector: 'webform-submission-taide-ja-kulttuuriavustukset-tai-form',
  formPath: '/fi/form/taide-ja-kulttuuriavustukset-tai',
  formPages: {
    "1_hakijan_tiedot": {
      items: {
        "edit-email": {
          value: getFakeEmailAddress(),
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
          viewPageFormatter: viewPageFormatCurrency
        },
        "edit-ensisijainen-taiteen-ala": {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: '',
            value: '#edit-ensisijainen-taiteen-ala',
          },
          viewPageSkipValidation: true,
        },
        "edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti": {
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
                    value: '1234',
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
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '3_yhteison_tiedot',
          },
          viewPageSkipValidation: true,
        },
      }
    },
    "3_yhteison_tiedot": {
      items: {
        "edit-members-applicant-person-global": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-members-applicant-person-local": {
          role: 'input',
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-members-applicant-community-global": {
          role: 'input',
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-members-applicant-community-local": {
          role: 'input',
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p": {
          role: 'dynamicmultivalue',
          label: '',
          dynamic_multi: {
            radioSelector: {
              type: 'dom-id-label',
              name: 'data-drupal-selector',
              value: 'edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p-1',
            },
            revealedElementSelector: {
              type: 'dom-id',
              name: '',
              value: '#edit-tila',
            },
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
                    value: faker.lorem.words(3),
                    viewPageSelector: '.form-item-tila',
                  },
                  {
                    role: 'select',
                    selector: {
                      type: 'by-label',
                      name: '',
                      value: 'edit-tila-items-[INDEX]-item-premisetype'
                    },
                    value: 'Näyttelytila',
                    viewPageSelector: '.form-item-tila',
                  },
                  {
                    role: 'input',
                    selector: {
                      type: 'data-drupal-selector',
                      name: 'data-drupal-selector',
                      value: 'edit-tila-items-[INDEX]-item-postcode',
                    },
                    value: '00100',
                    viewPageSelector: '.form-item-tila',
                  },
                  {
                    role: 'radio',
                    selector: {
                      type: 'dom-id-label',
                      name: 'data-drupal-selector',
                      value: 'edit-tila-items-[INDEX]-item-isothersuse-1'
                    },
                    value: 'Tilaa tarjotaan muiden käyttöön (ilmaiseksi tai vuokralla)',
                    viewPageSelector: '.form-item-tila',
                  },
                  {
                    role: 'radio',
                    selector: {
                      type: 'dom-id-label',
                      name: 'data-drupal-selector',
                      value: 'edit-tila-items-[INDEX]-item-isownedbyapplicant-1'
                    },
                    value: 'Tila on omassa omistuksessa',
                    viewPageSelector: '.form-item-tila',
                  },
                  {
                    role: 'radio',
                    selector: {
                      type: 'dom-id-label',
                      name: 'data-drupal-selector',
                      value: 'edit-tila-items-[INDEX]-item-isownedbycity-1'
                    },
                    value: 'Kyseessä on kaupungin omistama tila',
                    viewPageSelector: '.form-item-tila',
                  },
                ],
              },
            },
          },
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '4_toiminta',
          },
          viewPageSkipValidation: true,
        },
      }
    },
    "4_toteutunut_toiminta": {
      items: {
        "edit-varhaisian-opinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-tytot-varhaisian-opinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-pojat-varhaisian-opinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-laaja-oppimaara-perusopinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-tytot-laaja-oppimaara-perusopinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-pojat-laaja-oppimaara-perusopinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-laaja-oppimaara-syventavat-opinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-tytot-laaja-oppimaara-syventavat-opinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-pojat-laaja-oppimaara-syventavat-opinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-yleinen-oppimaara": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-tytot-yleinen-oppimaara": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-pojat-yleinen-oppimaara": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-koko-opetushenkiloston-lukumaara-20-9": {
          value: faker.number.int({min: 10, max: 200}).toString(),
          viewPageSelector: '.form-item-koko-opetushenkiloston-lukumaara-20-9',
        },
        "edit-kuvaile-oppilaaksi-ottamisen-tapaa": {
          value: faker.lorem.lines(3)
        },
        "edit-tehdaanko-oppilaitoksessanne-tarvittaessa-oppimaaran-tai-opetuks": {
          value: faker.lorem.lines(3)
        },
        "edit-onko-vapaa-oppilaspaikkoja-montako-": {
          value: faker.lorem.lines(3),
          viewPageSelector: '.form-item-onko-vapaa-oppilaspaikkoja-montako-',
        },
        "edit-opetustunnit-varhaisian-opinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-opetustunnit-laaja-oppimaara-perusopinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-opetustunnit-laaja-oppimaara-syventavat-opinnot": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-opetustunnit-yleinen-oppimaara": {
          value: faker.number.int({min: 1, max: 9}).toString(),
        },
        "edit-helsingissa-jarjestettava-tila": {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-helsingissa-jarjestettava-tila-add-submit',
              resultValue: 'edit-helsingissa-jarjestettava-tila-items-[INDEX]'
            },
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-helsingissa-jarjestettava-tila-items-[INDEX]-item-premisename',
                  },
                  value: faker.lorem.words(3),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-helsingissa-jarjestettava-tila-items-[INDEX]-item-postcode',
                  },
                  value: '00100',
                },
                {
                  role: 'radio',
                  selector: {
                    type: 'dom-id-label',
                    name: 'data-drupal-selector',
                    value: 'edit-helsingissa-jarjestettava-tila-items-[INDEX]-item-isownedbycity-1'
                  },
                  value: 'Kyseessä on kaupungin omistama tila'
                },
                {
                  role: 'radio',
                  selector: {
                    type: 'dom-id-label',
                    name: 'data-drupal-selector',
                    value: 'edit-helsingissa-jarjestettava-tila-items-[INDEX]-item-premisesuitability-hyvin'
                  },
                  value: 'Kuinka hyvin tila soveltuu toimintaan?'
                },
              ],
            },
          },
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
      }
    },
    "5_toiminnan_lahtokohdat": {
      items: {
        "edit-toiminta-tasa-arvo": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-saavutettavuus": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-ekologisuus": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-tavoitteet": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-kaytetyt-keinot": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "edit-toiminta-tulevat-muutokset": {
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
        "edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-1',
          },
          value: "Kyllä",
          viewPageSelector: '.form-item-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-',
        },
        "edit-budget-static-income-plannedstateoperativesubvention": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-income',
          viewPageFormatter: viewPageFormatCurrency,
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
        "edit-budget-static-income-financialfundingandinterests": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-budget-static-income',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-suunnitellut-menot-plannedtotalcosts": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-suunnitellut-menot',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-toteutuneet-tulot-data-othercompensationfromcity": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-toteutuneet-tulot-data',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-toteutuneet-tulot-data-stateoperativesubvention": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-toteutuneet-tulot-data',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-toteutuneet-tulot-data-othercompensations": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-toteutuneet-tulot-data',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-toteutuneet-tulot-data-totalincome": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-toteutuneet-tulot-data',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-menot-yhteensa-totalcosts": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
          viewPageSelector: '.form-item-menot-yhteensa',
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-organisaatio-kuului-valtionosuusjarjestelmaan-vos-": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-organisaatio-kuului-valtionosuusjarjestelmaan-vos-1',
          },
          value: "Kyllä",
          viewPageSelector: '.form-item-organisaatio-kuului-valtionosuusjarjestelmaan-vos-'
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
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilinpaatos_edelliselta_paattyneelta_tilikaudelta__attachment]"]',
            resultValue: '.form-item-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta--attachment a',
          },
          value: ATTACHMENTS.VAHVISTETTU_TILINPAATOS,
          viewPageFormatter: viewPageFormatFilePath,
          viewPageSelector: '.form-item-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-',
        },
        'edit-vahvistettu-toimintakertomus-edelliselta-paattyneelta-tilikaudel-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_toimintakertomus_edelliselta_paattyneelta_tilikaudel_attachment]"]',
            resultValue: '.form-item-vahvistettu-toimintakertomus-edelliselta-paattyneelta-tilikaudel-attachment a',
          },
          value: ATTACHMENTS.VAHVISTETTU_TOIMINTAKERTOMUS,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-edelliselta-paa-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilin_tai_toiminnantarkastuskertomus_edelliselta_paa_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilin-tai-toiminnantarkastuskertomus-edelliselta-paa-attachment a',
          },
          value: ATTACHMENTS.VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-toimintasuunnitelma-sille-vuodelle-jolle-haet-avustusta-monivuot-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[toimintasuunnitelma_sille_vuodelle_jolle_haet_avustusta_monivuot_attachment]"]',
            resultValue: '.form-item-toimintasuunnitelma-sille-vuodelle-jolle-haet-avustusta-monivuot-attachment a',
          },
          value: ATTACHMENTS.TOIMINTASUUNNITELMA,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-talousarvio-sille-vuodelle-jolle-haet-avustusta-monivuotisissa-k-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[talousarvio_sille_vuodelle_jolle_haet_avustusta_monivuotisissa_k_attachment]"]',
            resultValue: '.form-item-talousarvio-sille-vuodelle-jolle-haet-avustusta-monivuotisissa-k-attachment a',
          },
          value: ATTACHMENTS.TALOUSARVIO,
          viewPageFormatter: viewPageFormatFilePath,
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
                  viewPageFormatter: viewPageFormatFilePath,
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
    }
  },
  expectedErrors: {},
  expectedDestination: '/fi/hakemus/taide_ja_kulttuuriavustukset_tai/',
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
      ],
      expectedInlineErrors: [
        {selector: '.form-item-bank-account-account-number-select', errorMessage: 'Valitse tilinumero kenttä on pakollinen.'},
        {selector: '.form-item-email', errorMessage: 'Hakemusta koskeva sähköposti kenttä on pakollinen.'},
        {selector: '.form-item-contact-person', errorMessage: 'Yhteyshenkilö kenttä on pakollinen.'},
        {selector: '.form-item-contact-person-phone-number', errorMessage: 'Puhelinnumero kenttä on pakollinen.'},
      ],
    },
    '2_avustustiedot': {
      items: {},
      itemsToRemove: [
        'edit-acting-year',
        'edit-subventions-items-0-amount',
        'edit-ensisijainen-taiteen-ala',
        'edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti',
      ],
      expectedInlineErrors: [
        {selector: '.form-item-acting-year', errorMessage: 'Vuosi, jolle haen avustusta kenttä on pakollinen.'},
        {selector: '.form-item-subventions', errorMessage: 'Sinun on syötettävä vähintään yhdelle avustuslajille summa'},
        {selector: '.form-item-ensisijainen-taiteen-ala', errorMessage: 'Ensisijainen taiteenala kenttä on pakollinen.'},
        {selector: '.form-item-hankkeen-tai-toiminnan-lyhyt-esittelyteksti', errorMessage: 'Hankkeen tai toiminnan lyhyt esittelyteksti kenttä on pakollinen.'},
      ],
    },
    '6_talous': {
      items: {},
      itemsToRemove: [
        'edit-budget-static-income-plannedstateoperativesubvention',
        'edit-budget-static-income-plannedothercompensations',
        'edit-budget-static-income-sponsorships',
        'edit-budget-static-income-entryfees',
        'edit-budget-static-income-sales',
        'edit-budget-static-income-financialfundingandinterests',
        'edit-suunnitellut-menot-plannedtotalcosts',
        'edit-toteutuneet-tulot-data-othercompensationfromcity',
        'edit-toteutuneet-tulot-data-stateoperativesubvention',
        'edit-toteutuneet-tulot-data-othercompensations',
        'edit-toteutuneet-tulot-data-totalincome',
        'edit-menot-yhteensa-totalcosts',
      ],
      expectedInlineErrors: [
        {selector: '.form-item-budget-static-income-plannedstateoperativesubvention', errorMessage: 'Valtion toiminta-avustus (€) kenttä on pakollinen.'},
        {selector: '.form-item-budget-static-income-plannedothercompensations', errorMessage: 'Muut avustukset (€) kenttä on pakollinen.'},
        {selector: '.form-item-budget-static-income-sponsorships', errorMessage: 'Yksityinen rahoitus (esim. sponsorointi, yritysyhteistyö,lahjoitukset) (€) kenttä on pakollinen.'},
        {selector: '.form-item-budget-static-income-entryfees', errorMessage: 'Pääsy- ja osallistumismaksut (€) kenttä on pakollinen.'},
        {selector: '.form-item-budget-static-income-sales', errorMessage: 'Muut oman toiminnan tulot (€) kenttä on pakollinen.'},
        {selector: '.form-item-budget-static-income-financialfundingandinterests', errorMessage: 'Rahoitus- ja korkotulot (€) kenttä on pakollinen.'},
        {selector: '.form-item-suunnitellut-menot-plannedtotalcosts', errorMessage: 'Menot yhteensä (€) kenttä on pakollinen.'},
        {selector: '.form-item-toteutuneet-tulot-data-othercompensationfromcity', errorMessage: 'Helsingin kaupungin kulttuuripalveluiden toiminta-avustus (€) kenttä on pakollinen.'},
        {selector: '.form-item-toteutuneet-tulot-data-stateoperativesubvention', errorMessage: 'Valtion toiminta-avustus (€) kenttä on pakollinen. '},
        {selector: '.form-item-toteutuneet-tulot-data-othercompensations', errorMessage: 'Muut avustukset (€) kenttä on pakollinen. '},
        {selector: '.form-item-toteutuneet-tulot-data-totalincome', errorMessage: 'Tulot yhteensä (€) kenttä on pakollinen. '},
        {selector: '.form-item-menot-yhteensa-totalcosts', errorMessage: 'Menot yhteensä (€) kenttä on pakollinen. '},
      ],
    },
  },
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Hakemusta koskeva sähköposti kenttä on pakollinen.',
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Hakemusta koskeva sähköposti kenttä on pakollinen.',
    'edit-contact-person': 'Virhe sivulla 1. Hakijan tiedot: Yhteyshenkilö kenttä on pakollinen.',
    'edit-contact-person-phone-number': 'Virhe sivulla 1. Hakijan tiedot: Puhelinnumero kenttä on pakollinen.',
    'edit-community-address': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-ensisijainen-taiteen-ala': 'Virhe sivulla 2. Avustustiedot: Ensisijainen taiteenala kenttä on pakollinen.',
    'edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti': 'Virhe sivulla 2. Avustustiedot: Hankkeen tai toiminnan lyhyt esittelyteksti kenttä on pakollinen.',
    'edit-budget-static-income-plannedstateoperativesubvention': 'Virhe sivulla 6. Talous: Valtion toiminta-avustus (€) kenttä on pakollinen.',
    'edit-budget-static-income-plannedothercompensations': 'Virhe sivulla 6. Talous: Muut avustukset (€) kenttä on pakollinen.',
    'edit-budget-static-income-sponsorships': 'Virhe sivulla 6. Talous: Yksityinen rahoitus (esim. sponsorointi, yritysyhteistyö,lahjoitukset) (€) kenttä on pakollinen.',
    'edit-budget-static-income-entryfees': 'Virhe sivulla 6. Talous: Pääsy- ja osallistumismaksut (€) kenttä on pakollinen.',
    'edit-budget-static-income-sales': 'Virhe sivulla 6. Talous: Muut oman toiminnan tulot (€) kenttä on pakollinen.',
    'edit-budget-static-income-financialfundingandinterests': 'Virhe sivulla 6. Talous: Rahoitus- ja korkotulot (€) kenttä on pakollinen.',
    'edit-suunnitellut-menot-plannedtotalcosts': 'Virhe sivulla 6. Talous: Menot yhteensä (€) kenttä on pakollinen.',
    'edit-toteutuneet-tulot-data-othercompensationfromcity': 'Virhe sivulla 6. Talous: Helsingin kaupungin kulttuuripalveluiden toiminta-avustus (€) kenttä on pakollinen.',
    'edit-toteutuneet-tulot-data-stateoperativesubvention': 'Virhe sivulla 6. Talous: Valtion toiminta-avustus (€) kenttä on pakollinen.',
    'edit-toteutuneet-tulot-data-othercompensations': 'Virhe sivulla 6. Talous: Muut avustukset (€) kenttä on pakollinen.',
    'edit-toteutuneet-tulot-data-totalincome': 'Virhe sivulla 6. Talous: Tulot yhteensä (€) kenttä on pakollinen.',
    'edit-menot-yhteensa-totalcosts': 'Virhe sivulla 6. Talous: Menot yhteensä (€) kenttä on pakollinen.',
  },
};

const registeredCommunityApplications_50 = {
  draft: baseFormRegisteredCommunity_50,
  missing_values: createFormData(baseFormRegisteredCommunity_50, missingValues),
  /**
   * @todo enable when sending to avus2 works.
   */
  // success: createFormData(baseFormRegisteredCommunity_50, sendApplication),
};

export {
  registeredCommunityApplications_50,
};

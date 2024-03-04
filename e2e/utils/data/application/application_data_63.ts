import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {
  PATH_TO_TEST_PDF,
  PATH_YHTEISON_SAANNOT,
  PATH_VAHVISTETTU_TILINPAATOS,
  PATH_VAHVISTETTU_TOIMINTAKERTOMUS,
  PATH_VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS,
  PATH_VUOSIKOKOUKSEN_POYTAKIRJA,
  PATH_TOIMINTASUUNNITELMA,
  PATH_TALOUSARVIO,
  PATH_MUU_LIITE,
  PATH_LEIRIEXCEL,
} from "../../helpers";
import {createFormData} from "../../form_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatBoolean,
  viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatCurrency,
  viewPageFormatNumber,
} from "../../view_page_formatters";
import {PROFILE_INPUT_DATA} from "../profile_input_data";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_63: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-nuortoimpalkka-form',
  formPath: '/fi/form/nuortoimpalkka',
  formPages: {
    "1_hakijan_tiedot": {
      items: {
        "edit-email": {
          value: 'test@test.fi',
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
            name: 'bank-account-selector',
            value: '#edit-acting-year',
          },
          value: '2024',
        },
        "edit-subventions-items-1-amount": {
          value: '5709,98',
          viewPageSelector: '.form-item-subventions',
          viewPageFormatter: viewPageFormatCurrency
        },
        "edit-haen-vuokra-avustusta-1": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-haen-vuokra-avustusta-1',
          },
          value: "Kyllä",
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
                type: 'add-more-button',
                name: 'data-drupal-selector',
                value: 'Lisää uusi myönnetty avustus',
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

        "edit-haettu-avustus-tieto": {
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
                type: 'add-more-button',
                name: 'data-drupal-selector',
                value: 'Lisää uusi haettu avustus',
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
                      precision: 2
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
                      precision: 2
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
        "edit-jasenet-0-6-vuotiaat": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-jasenet-0-6-vuotiaat',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-0-6-joista-helsinkilaisia": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-_-6-joista-helsinkilaisia',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-jasenet-7-28-vuotiaat": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-jasenet-7-28-vuotiaat',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-7-28-joista-helsinkilaisia": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-_-28-joista-helsinkilaisia',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-muut-jasenet-tai-aktiiviset-osallistujat": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-muut-joista-helsinkilaisia": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli',
          viewPageFormatter: viewPageFormatNumber
        },
        "edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-0": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-0',
          },
          value: "Ei",
          viewPageFormatter: viewPageFormatBoolean,
        },
        "edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa-items-0-item-location": {
          value: faker.lorem.words(2),
          viewPageSelector: '.form-item-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa',
        },
        "edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa-items-0-item-postcode": {
          value: '20100',
          viewPageSelector: '.form-item-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa',
        },
        "edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa-items-0-item-organizationname": {
          value: faker.lorem.words(2),
          viewPageSelector: '.form-item-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa',
        },
        "edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa-items-0-item-fee": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa',
          viewPageFormatter: viewPageFormatCurrency
        },
        "edit-miten-nuoret-osallistuvat-yhdistyksen-toiminnan-suunnitteluun-ja": {
          role: 'input',
          value: faker.lorem.sentences(3),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '4_palkkaustiedot',
          },
          viewPageSkipValidation: true,
        },
      },
    },
    "4_palkkaustiedot": {
      items: {
        "edit-kuinka-monta-paatoimista-palkattua-tyontekijaa-yhdistyksessa-tyo": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatNumber,
        },
        "edit-palkkauskulut": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-lakisaateiset-ja-vapaaehtoiset-henkilosivukulut": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatCurrency,
        },
        "edit-matka-ja-koulutuskulut": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageFormatter: viewPageFormatCurrency,
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'vuokra_avustushakemuksen_tiedot',
          },
          viewPageSkipValidation: true,
        },
      },
    },
    "vuokra_avustushakemuksen_tiedot": {
      items: {
        "edit-vuokratun-tilan-tiedot-items-0-item-premiseaddress": {
          value: faker.location.streetAddress(),
          viewPageSelector: '.form-item-vuokratun-tilan-tiedot',
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-premisepostalcode": {
          value: faker.location.zipCode(),
          viewPageSelector: '.form-item-vuokratun-tilan-tiedot',
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-premisepostoffice": {
          value: faker.location.city(),
          viewPageSelector: '.form-item-vuokratun-tilan-tiedot',
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-rentsum": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
          viewPageSelector: '.form-item-vuokratun-tilan-tiedot',
          viewPageFormatter: viewPageFormatCurrency,
          selector: {
            type: 'data-drupal-selector-sequential',
            name: 'data-drupal-selector',
            value: 'edit-vuokratun-tilan-tiedot-items-0-item-rentsum',
          }
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-lessorname": {
          value: faker.person.fullName(),
          viewPageSelector: '.form-item-vuokratun-tilan-tiedot',
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-lessorphoneoremail": {
          value: faker.phone.number(),
          viewPageSelector: '.form-item-vuokratun-tilan-tiedot',
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-usage": {
          value: faker.lorem.words(10),
          viewPageSelector: '.form-item-vuokratun-tilan-tiedot',
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-daysperweek": {
          value: faker.number.int({min: 1, max: 7}).toString(),
          viewPageSelector: '.form-item-vuokratun-tilan-tiedot',
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-hoursperday": {
          value: faker.number.int({min: 1, max: 24}).toString(),
          viewPageSelector: '.form-item-vuokratun-tilan-tiedot',
        },
        "edit-lisatiedot": {
          role: 'input',
          value: faker.lorem.sentences(3),
          viewPageSelector: '.form-item-lisatiedot',
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
          value: PATH_YHTEISON_SAANNOT,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-vahvistettu-tilinpaatos-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilinpaatos_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilinpaatos-attachment a',
          },
          value: PATH_VAHVISTETTU_TILINPAATOS,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-vahvistettu-toimintakertomus-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_toimintakertomus_attachment]"]',
            resultValue: '.form-item-vahvistettu-toimintakertomus-attachment a',
          },
          value: PATH_VAHVISTETTU_TOIMINTAKERTOMUS,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilin_tai_toiminnantarkastuskertomus_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment a',
          },
          value: PATH_VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-vuosikokouksen-poytakirja-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vuosikokouksen_poytakirja_attachment]"]',
            resultValue: '.form-item-vuosikokouksen-poytakirja-attachment a',
          },
          value: PATH_VUOSIKOKOUKSEN_POYTAKIRJA,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-toimintasuunnitelma-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[toimintasuunnitelma_attachment]"]',
            resultValue: '.form-item-toimintasuunnitelma-attachment a',
          },
          value: PATH_TOIMINTASUUNNITELMA,
          viewPageFormatter: viewPageFormatFilePath,
        },
        'edit-talousarvio-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[talousarvio_attachment]"]',
            resultValue: '.form-item-talousarvio-attachment a',
          },
          value: PATH_TALOUSARVIO,
          viewPageFormatter: viewPageFormatFilePath,
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
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-muu-liite-items-0-item-description',
          },
          value: faker.lorem.sentences(1),
          viewPageSelector: '.form-item-muu-liite',
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
          viewPageSkipValidation: true
        },
      },
    },
    "webform_preview": {
      items: {
        "accept_terms_1": {
          role: 'checkbox',
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
          viewPageSkipValidation: true,
        },
      },
    },
  },
  expectedErrors: {},
  expectedDestination: "/fi/hakemus/nuortoimpalkka/",
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
        'edit-acting-year',
        'edit-subventions-items-1-amount',
      ],
    },
    '3_yhteison_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-jasenet-0-6-vuotiaat',
        'edit-0-6-joista-helsinkilaisia',
        'edit-jasenet-7-28-vuotiaat',
        'edit-7-28-joista-helsinkilaisia',
        'edit-muut-jasenet-tai-aktiiviset-osallistujat',
        'edit-muut-joista-helsinkilaisia',
        'edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter',
        'edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli',
        'edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-0',
        'edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa-items-0-item-location',
        'edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa-items-0-item-postcode',
      ],
    },
    '4_palkkaustiedot': {
      items: {},
      itemsToRemove: [
        'edit-kuinka-monta-paatoimista-palkattua-tyontekijaa-yhdistyksessa-tyo',
        'edit-palkkauskulut',
        'edit-lakisaateiset-ja-vapaaehtoiset-henkilosivukulut',
        'edit-matka-ja-koulutuskulut',
      ],
    },
    'vuokra_avustushakemuksen_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-vuokratun-tilan-tiedot-items-0-item-premiseaddress',
        'edit-vuokratun-tilan-tiedot-items-0-item-premisepostalcode',
        'edit-vuokratun-tilan-tiedot-items-0-item-premisepostoffice',
        'edit-vuokratun-tilan-tiedot-items-0-item-rentsum',
        'edit-vuokratun-tilan-tiedot-items-0-item-lessorname',
        'edit-vuokratun-tilan-tiedot-items-0-item-lessorphoneoremail',
        'edit-vuokratun-tilan-tiedot-items-0-item-usage',
        'edit-vuokratun-tilan-tiedot-items-0-item-daysperweek',
        'edit-vuokratun-tilan-tiedot-items-0-item-hoursperday',
      ],
    },
    'lisatiedot_ja_liitteet': {
      items: {},
      itemsToRemove: [
        'edit-yhteison-saannot-attachment-upload',
        'edit-vahvistettu-tilinpaatos-attachment-upload',
        'edit-vahvistettu-toimintakertomus-attachment-upload',
        'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload',
        'edit-vuosikokouksen-poytakirja-attachment-upload',
        'edit-toimintasuunnitelma-attachment-upload',
        'edit-talousarvio-attachment-upload',
      ],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite kenttä on pakollinen.',
    'edit-contact-person': 'Virhe sivulla 1. Hakijan tiedot: Yhteyshenkilö kenttä on pakollinen.',
    'edit-contact-person-phone-number': 'Virhe sivulla 1. Hakijan tiedot: Puhelinnumero kenttä on pakollinen.',
    'edit-community-address': 'Virhe sivulla 1. Hakijan tiedot: Yhteisön osoite kenttä on pakollinen.',
    'edit-community-address-community-address-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse osoite kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-subventions-items-1-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-jasenet-0-6-vuotiaat': 'Virhe sivulla 3. Yhteisön toiminta: Jäsenet 0 - 6 vuotiaat kenttä on pakollinen.',
    'edit-0-6-joista-helsinkilaisia': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-jasenet-7-28-vuotiaat': 'Virhe sivulla 3. Yhteisön toiminta: Jäsenet 7 - 28 vuotiaat kenttä on pakollinen.',
    'edit-7-28-joista-helsinkilaisia': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-muut-jasenet-tai-aktiiviset-osallistujat': 'Virhe sivulla 3. Yhteisön toiminta: Muut jäsenet tai aktiiviset osallistujat kenttä on pakollinen.',
    'edit-muut-joista-helsinkilaisia': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter': 'Virhe sivulla 3. Yhteisön toiminta: Alle 29-vuotiaiden kaikki osallistumiskerrat edellisenä kalenterivuotena kenttä on pakollinen.',
    'edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli': 'Virhe sivulla 3. Yhteisön toiminta: Joista alle 29-vuotiaiden digitaalisia osallistumiskertoja oli kenttä on pakollinen.',
    'edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-0': 'Virhe sivulla 3. Yhteisön toiminta: Järjestimme toimintaa vain digitaalisessa ympäristössä kenttä on pakollinen.',
    'edit-vuokratun-tilan-tiedot-items-0-item-premiseaddress': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Katuosoite kenttä on pakollinen.',
    'edit-vuokratun-tilan-tiedot-items-0-item-premisepostalcode': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Postinumero kenttä on pakollinen.',
    'edit-vuokratun-tilan-tiedot-items-0-item-premisepostoffice': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Postitoimipaikka kenttä on pakollinen.',
    'edit-vuokratun-tilan-tiedot-items-0-item-rentsum': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Vuokra kenttä on pakollinen.',
    'edit-vuokratun-tilan-tiedot-items-0-item-lessorname': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Vuokranantajan nimi kenttä on pakollinen.',
    'edit-vuokratun-tilan-tiedot-items-0-item-lessorphoneoremail': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Vuokranantajan yhteystiedot kenttä on pakollinen.',
    'edit-vuokratun-tilan-tiedot-items-0-item-usage': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Käyttötarkoitus kenttä on pakollinen.',
    'edit-vuokratun-tilan-tiedot-items-0-item-daysperweek': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Kuinka monena päivänä viikossa tilassa on toimintaa? kenttä on pakollinen.',
    'edit-vuokratun-tilan-tiedot-items-0-item-hoursperday': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Kuinka monta tuntia päivässä tilassa on toimintaa? kenttä on pakollinen.',
    'edit-yhteison-saannot-attachment-upload': 'Virhe sivulla 6. Lisätiedot ja liitteet: Yhteisön säännöt ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vahvistettu-tilinpaatos-attachment-upload': 'Virhe sivulla 6. Lisätiedot ja liitteet: Vahvistettu tilinpäätös (edelliseltä päättyneeltä tilikaudelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vahvistettu-toimintakertomus-attachment-upload': 'Virhe sivulla 6. Lisätiedot ja liitteet: Vahvistettu toimintakertomus (edelliseltä päättyneeltä tilikaudelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload': 'Virhe sivulla 6. Lisätiedot ja liitteet: Vahvistettu tilin- tai toiminnantarkastuskertomus (edelliseltä päättyneeltä tilikaudelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-vuosikokouksen-poytakirja-attachment-upload': 'Virhe sivulla 6. Lisätiedot ja liitteet: Vuosikokouksen pöytäkirja, jossa on vahvistettu edellisen päättyneen tilikauden tilinpäätös ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-toimintasuunnitelma-attachment-upload': 'Virhe sivulla 6. Lisätiedot ja liitteet: Toimintasuunnitelma (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-talousarvio-attachment-upload': 'Virhe sivulla 6. Lisätiedot ja liitteet: Talousarvio (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
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
    '3_yhteison_tiedot': {
      items: {
        "edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa-items-0-item-postcode": {
          role: 'input',
          value: 'fgdrg',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa-items-0-item-postcode',
          }
        },
      },
      itemsToRemove: [],
    },
    'vuokra_avustushakemuksen_tiedot': {
      items: {
        "edit-vuokratun-tilan-tiedot-items-0-item-premisepostalcode": {
          role: 'input',
          value: 'fgdrg',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-vuokratun-tilan-tiedot-items-0-item-premisepostalcode',
          }
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-daysperweek": {
          role: 'input',
          value: 'fgdrg',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-vuokratun-tilan-tiedot-items-0-item-daysperweek',
          }
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-hoursperday": {
          role: 'input',
          value: 'fgdrg',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-vuokratun-tilan-tiedot-items-0-item-hoursperday',
          }
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite ääkkösiävaa ei kelpaa.',
    'edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa-items-0-item-postcode': 'Virhe sivulla 3. Yhteisön toiminta: Käytä muotoa FI-XXXXX tai syötä postinumero viisinumeroisena.',
    'edit-vuokratun-tilan-tiedot-items-0-item-premisepostalcode': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Käytä muotoa FI-XXXXX tai syötä postinumero viisinumeroisena.',
    'edit-vuokratun-tilan-tiedot-items-0-item-daysperweek': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Kuinka monena päivänä viikossa tilassa on toimintaa?n on oltava numero.',
    'edit-vuokratun-tilan-tiedot-items-0-item-hoursperday': 'Virhe sivulla 5. Vuokra-avustushakemuksen tiedot: Kuinka monta tuntia päivässä tilassa on toimintaa?n on oltava numero.',
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
  expectedDestination: '',
  expectedErrors: {},
};

const registeredCommunityApplications_63 = {
  draft: baseFormRegisteredCommunity_63,
  missing_values: createFormData(baseFormRegisteredCommunity_63, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_63, wrongValues),
  // success: createFormData(baseFormRegisteredCommunity_63, sendApplication),
}

export {
  registeredCommunityApplications_63
}

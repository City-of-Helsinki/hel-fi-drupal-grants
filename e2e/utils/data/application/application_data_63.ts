import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PATH_TO_TEST_PDF} from "../../helpers";
import {createFormData} from "../../form_helpers";

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
          value: 'haloo@haloo.fi',
          // emails created by faker were not accepted for some reason?
        },
        "edit-contact-person": {
          value: faker.person.fullName(),
        },
        "edit-contact-person-phone-number": {
          value: faker.phone.number(),
        },
        "edit-bank-account-account-number-select": {
          role: 'select',
          value: 'use-random-value',
        },
        "edit-community-address-community-address-select": {
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
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '2_avustustiedot',
          }
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
        },
        "edit-haen-vuokra-avustusta-1": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-haen-vuokra-avustusta-1',
          },
          value: "1",
        },
        // muut samaan tarkoitukseen myönnetyt
        // muut samaan tarkoitukseen haetut
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
    "3_yhteison_tiedot": {
      items: {
        "edit-jasenet-0-6-vuotiaat": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-0-6-joista-helsinkilaisia": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-jasenet-7-28-vuotiaat": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-7-28-joista-helsinkilaisia": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-muut-jasenet-tai-aktiiviset-osallistujat": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-muut-joista-helsinkilaisia": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-1": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-1',
          },
          value: "1",
        },
        "edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa-items-0-item-organizationname": {
          value: faker.lorem.words(2),
        },
        "edit-jasenyydet-jarjestoissa-ja-muissa-yhteisoissa-items-0-item-fee": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
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
          }
        },
      },
    },
    "4_palkkaustiedot": {
      items: {
        "edit-kuinka-monta-paatoimista-palkattua-tyontekijaa-yhdistyksessa-tyo": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-palkkauskulut": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-lakisaateiset-ja-vapaaehtoiset-henkilosivukulut": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-matka-ja-koulutuskulut": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'vuokra_avustushakemuksen_tiedot',
          }
        },
      },
    },
    "vuokra_avustushakemuksen_tiedot": {
      items: {
        "edit-vuokratun-tilan-tiedot-items-0-item-premiseaddress": {
          value: faker.location.streetAddress(),
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-premisepostalcode": {
          value: faker.location.zipCode(),
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-premisepostoffice": {
          value: faker.location.city(),
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-rentsum": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-lessorname": {
          value: faker.person.fullName(),
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-lessorphoneoremail": {
          value: faker.phone.number(),
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-usage": {
          value: faker.lorem.words(10),
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-daysperweek": {
          value: faker.number.int({min: 1, max: 7}).toString(),
        },
        "edit-vuokratun-tilan-tiedot-items-0-item-hoursperday": {
          value: faker.number.int({min: 1, max: 24}).toString(),
        },
        "edit-lisatiedot": {
          role: 'input',
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
          value: PATH_TO_TEST_PDF,
        },
        'edit-vahvistettu-tilinpaatos-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilinpaatos_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilinpaatos-attachment a',
          },
          value: PATH_TO_TEST_PDF,
        },
        'edit-vahvistettu-toimintakertomus-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_toimintakertomus_attachment]"]',
            resultValue: '.form-item-vahvistettu-toimintakertomus-attachment a',
          },
          value: PATH_TO_TEST_PDF,
        },
        'edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilin_tai_toiminnantarkastuskertomus_attachment]"]',
            resultValue: '.form-item-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment a',
          },
          value: PATH_TO_TEST_PDF,
        },
        'edit-vuosikokouksen-poytakirja-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vuosikokouksen_poytakirja_attachment]"]',
            resultValue: '.form-item-vuosikokouksen-poytakirja-attachment a',
          },
          value: PATH_TO_TEST_PDF,
        },
        'edit-toimintasuunnitelma-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[toimintasuunnitelma_attachment]"]',
            resultValue: '.form-item-toimintasuunnitelma-attachment a',
          },
          value: PATH_TO_TEST_PDF,
        },
        'edit-talousarvio-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[talousarvio_attachment]"]',
            resultValue: '.form-item-talousarvio-attachment a',
          },
          value: PATH_TO_TEST_PDF,
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
  expectedDestination: "/fi/hakemus/nuortoimpalkka/",
}

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values from 1st page',
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      // itemsToRemove: ['edit-bank-account-account-number-select'],
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
          }
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    // 'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
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


const registeredCommunityApplications_63 = {
  success: baseFormRegisteredCommunity_63,
  draft: createFormData(baseFormRegisteredCommunity_63, saveDraft),
}

export {
  registeredCommunityApplications_63
}

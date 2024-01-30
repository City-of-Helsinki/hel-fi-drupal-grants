import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {
  PATH_YHTEISON_SAANNOT,
  PATH_TOIMINTASUUNNITELMA,
  PATH_TALOUSARVIO,
  PATH_MUU_LIITE,
} from "../../helpers";
import {createFormData} from "../../form_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatBoolean, viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatCurrency
} from "../../view_page_formatters";
import {PROFILE_INPUT_DATA} from "../profile_input_data";

/**
 * Basic form data for successful submit to Avus2
 */
const baseFormRegisteredCommunity_62: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-nuorisotoiminta-projektiavustush-form',
  formPath: '/fi/form/nuorisotoiminta-projektiavustush',
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
        "edit-kenelle-haen-avustusta": {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: 'bank-account-selector',
            value: '#edit-kenelle-haen-avustusta',
          },
          value: 'Nuorisoyhdistys',
        },
        "edit-acting-year": {
          role: 'select',
          selector: {
            type: 'dom-id-first',
            name: 'bank-account-selector',
            value: '#edit-acting-year',
          },
          value: '2024',
        },
        "edit-subventions-items-0-amount": {
          value: '5709,98',
        },
        // muut samaan tarkoitukseen myönnetyt
        // muut samaan tarkoitukseen haetut
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '3_jasenet_tai_aktiiviset_osallistujat',
          }
        },
      },
    },
    "3_jasenet_tai_aktiiviset_osallistujat": {
      items: {
        "edit-jasenet-7-28": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-jasenet-kaikki": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: 'projektisuunnitelma',
          }
        },
      },
    },
    "projektisuunnitelma": {
      items: {
        "edit-projektin-nimi": {
          value: faker.lorem.words(4),
        },
        "edit-projektin-tavoitteet": {
          value: faker.lorem.sentences(4),
        },
        "edit-projektin-sisalto": {
          value: faker.lorem.sentences(4),
        },
        "edit-projekti-alkaa": {
          value: '2023-12-01',
        },
        "edit-projekti-loppuu": {
          value: '2023-12-31',
        },
        "edit-osallistujat-7-28": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-osallistujat-kaikki": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-projektin-paikka-2": {
          value: faker.lorem.sentences(4),
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
        "edit-omarahoitusosuuden-kuvaus": {
          value: faker.lorem.sentences(4),
        },
        "edit-omarahoitusosuus": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-budget-other-income-items-0-item-label": {
          value: faker.lorem.words(3),
        },
        "edit-budget-other-income-items-0-item-value": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
        },
        "edit-budget-other-cost-items-0-item-label": {
          value: faker.lorem.words(3),
        },
        "edit-budget-other-cost-items-0-item-value": {
          value: faker.number.int({min: 12, max: 5000}).toString(),
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
          value: PATH_YHTEISON_SAANNOT,
        },

        'edit-projektisuunnitelma-liite-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[projektisuunnitelma_liite_attachment]"]',
            resultValue: '.form-item-projektisuunnitelma-liite-attachment a',
          },
          value: PATH_TOIMINTASUUNNITELMA,
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
        },
        'edit-muu-liite-items-0-item-description': {
          role: 'input',
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
          value: 'save-draft',
          selector: {
            type: 'data-drupal-selector',
            name: 'data-drupal-selector',
            value: 'edit-actions-draft',
          }
        },
      },
    },
  },
  expectedErrors: {},
  expectedDestination: "/fi/hakemus/nuorisotoiminta_projektiavustush/",
}

const missingValues: FormDataWithRemoveOptionalProps = {
  title: 'Missing values',
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
        'edit-kenelle-haen-avustusta',
        'edit-acting-year',
        'edit-subventions-items-0-amount',
      ],
    },
    '3_yhteison_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-jasenet-7-28',
        'edit-jasenet-kaikki',
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
    'webform_preview': {
      items: {},
      itemsToRemove: [],
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
    'edit-kenelle-haen-avustusta': 'Virhe sivulla 2. Avustustiedot: Kenelle haen avustusta kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-jasenet-7-28': 'Virhe sivulla 3. Jäsenet tai aktiiviset osallistujat: Kuinka monta 7-28 -vuotiasta helsinkiläistä jäsentä tai aktiivista osallistujaa nuorten toimintaryhmässä / yhdistyksessä / talokerhossa on? kenttä on pakollinen.',
    'edit-jasenet-kaikki': 'Virhe sivulla 3. Jäsenet tai aktiiviset osallistujat: Kuinka monta jäsentä tai aktiivista osallistujaa nuorten toimintaryhmässä / yhdistyksessä / talokerhossa on yhteensä? kenttä on pakollinen.',
    'edit-projektin-nimi': 'Virhe sivulla 4. Projektisuunnitelma: Projektin nimi kenttä on pakollinen.',
    'edit-projekti-alkaa': 'Virhe sivulla 4. Projektisuunnitelma: Projekti alkaa kenttä on pakollinen.',
    'edit-projekti-loppuu': 'Virhe sivulla 4. Projektisuunnitelma: Projekti loppuu kenttä on pakollinen.',
    'edit-osallistujat-7-28': 'Virhe sivulla 4. Projektisuunnitelma: Kuinka monta 7-28 -vuotiasta helsinkiläistä projektiin osallistuu? kenttä on pakollinen.',
    'edit-osallistujat-kaikki': 'Virhe sivulla 4. Projektisuunnitelma: Kuinka paljon projektin osallistujia on yhteensä? kenttä on pakollinen.',

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
          }
        },
      },
      itemsToRemove: [],
    },
  },
  expectedDestination: '',
  expectedErrors: {},
};

const registeredCommunityApplications_62 = {
  draft: baseFormRegisteredCommunity_62,
  missing_values: createFormData(baseFormRegisteredCommunity_62, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_62, wrongValues),
  // success: createFormData(baseFormRegisteredCommunity_62, sendApplication),
}

export {
  registeredCommunityApplications_62
}

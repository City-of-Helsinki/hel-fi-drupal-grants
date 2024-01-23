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
        "edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-0": {
          role: 'radio',
          selector: {
            type: 'dom-id-label',
            name: 'data-drupal-selector',
            value: 'edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-0',
          },
          value: "0",
        },
        "edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa-items-0-item-location": {
          value: faker.lorem.words(2),
        },
        "edit-jarjestimme-toimintaa-nuorille-seuraavissa-paikoissa-items-0-item-postcode": {
          value: '20100',
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
          value: PATH_YHTEISON_SAANNOT,
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
        'muu_liite_0': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[muu_liite_items_0__item__attachment]"]',
            resultValue: '.form-item-muu-liite-items-0--item--attachment a',
          },
          value: PATH_MUU_LIITE,
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
        'edit-acting-year',
        'edit-subventions-items-0-amount',
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
        'edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-1',
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
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite kenttä on pakollinen.',
    'edit-contact-person': 'Virhe sivulla 1. Hakijan tiedot: Yhteyshenkilö kenttä on pakollinen.',
    'edit-contact-person-phone-number': 'Virhe sivulla 1. Hakijan tiedot: Puhelinnumero kenttä on pakollinen.',
    'edit-community-address': 'Virhe sivulla 1. Hakijan tiedot: Yhteisön osoite kenttä on pakollinen.',
    'edit-community-address-community-address-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse osoite kenttä on pakollinen.',
    'edit-acting-year': 'Virhe sivulla 2. Avustustiedot: Vuosi, jolle haen avustusta kenttä on pakollinen.',
    'edit-subventions-items-0-amount': 'Virhe sivulla 2. Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
    'edit-jasenet-0-6-vuotiaat': 'Virhe sivulla 3. Yhteisön toiminta: Jäsenet 0 - 6 vuotiaat kenttä on pakollinen.',
    'edit-0-6-joista-helsinkilaisia': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-jasenet-7-28-vuotiaat': 'Virhe sivulla 3. Yhteisön toiminta: Jäsenet 7 - 28 vuotiaat kenttä on pakollinen.',
    'edit-7-28-joista-helsinkilaisia': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-muut-jasenet-tai-aktiiviset-osallistujat': 'Virhe sivulla 3. Yhteisön toiminta: Muut jäsenet tai aktiiviset osallistujat kenttä on pakollinen.',
    'edit-muut-joista-helsinkilaisia': 'Virhe sivulla 3. Yhteisön toiminta: Joista helsinkiläisiä kenttä on pakollinen.',
    'edit-alle-29-vuotiaiden-kaikki-osallistumiskerrat-edellisena-kalenter': 'Virhe sivulla 3. Yhteisön toiminta: Alle 29-vuotiaiden kaikki osallistumiskerrat edellisenä kalenterivuotena kenttä on pakollinen.',
    'edit-joista-alle-29-vuotiaiden-digitaalisia-osallistumiskertoja-oli': 'Virhe sivulla 3. Yhteisön toiminta: Joista alle 29-vuotiaiden digitaalisia osallistumiskertoja oli kenttä on pakollinen.',
    'edit-jarjestimme-toimintaa-vain-digitaalisessa-ymparistossa-1': 'Virhe sivulla 3. Yhteisön toiminta: Järjestimme toimintaa vain digitaalisessa ympäristössä kenttä on pakollinen.',
    'edit-kuinka-monta-paatoimista-palkattua-tyontekijaa-yhdistyksessa-tyo': 'Virhe sivulla 4. Palkkaustiedot: Kuinka monta päätoimista palkattua työntekijää yhdistyksessä työskentelee? kenttä on pakollinen.',
    'edit-palkkauskulut': 'Virhe sivulla 4. Palkkaustiedot: Palkkauskulut kenttä on pakollinen.',
    'edit-lakisaateiset-ja-vapaaehtoiset-henkilosivukulut': 'Virhe sivulla 4. Palkkaustiedot: Lakisääteiset ja vapaaehtoiset henkilösivukulut kenttä on pakollinen.',
    'edit-matka-ja-koulutuskulut': 'Virhe sivulla 4. Palkkaustiedot: Matka- ja koulutuskulut kenttä on pakollinen.',
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
  missing_values: createFormData(baseFormRegisteredCommunity_63, missingValues),
  wrong_values: createFormData(baseFormRegisteredCommunity_63, wrongValues),
}

export {
  registeredCommunityApplications_63
}

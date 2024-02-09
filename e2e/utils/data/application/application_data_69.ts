import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {
  PATH_YHTEISON_SAANNOT,
  PATH_MUU_LIITE,
  PATH_LEIRIEXCEL,
  PATH_VAHVISTETTU_TILINPAATOS,
} from "../../helpers";
import {createFormData} from "../../form_helpers";

/**
 * Basic form data for successful submit to Avus2
 */
const baseForm_69: FormData = {
  title: 'Form submit',
  formSelector: 'webform-submission-leiriselvitys-form',
  formPath: '/fi/form/leiriselvitys',
  formPages: {
    "1_hakijan_tiedot": {
      items: {
        "edit-email": {
          value: 'email@email.fi',
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
        "edit-jarjestimme-leireja-seuraavilla-alueilla-items-0-item-premisename": {
          role: 'input',
          value: faker.lorem.words(2),
        },
        "edit-jarjestimme-leireja-seuraavilla-alueilla-items-0-item-postcode": {
          role: 'input',
          value: '20100',
        },
        "nextbutton": {
          role: 'button',
          selector: {
            type: 'form-topnavi-link',
            name: 'data-drupal-selector',
            value: '3_talousarvio',
          }
        },
      },
    },
    "3_talousarvio": {
      items: {
        "edit-tulo-items-0-item-label": {
          role: 'input',
          value: faker.lorem.words(2),
        },
        "edit-tulo-items-0-item-value": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
        },
        "edit-meno-items-0-item-label": {
          role: 'input',
          value: faker.lorem.words(2),
        },
        "edit-meno-items-0-item-value": {
          role: 'input',
          value: faker.number.int({min: 1, max: 5000}).toString(),
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
        'edit-leiri-excel-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[leiri_excel_attachment]"]',
            resultValue: '.form-item-leiri-excel-attachment a',
          },
          value: PATH_LEIRIEXCEL,
        },
        'edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[vahvistettu_tilinpaatos_edelliselta_paattyneelta_tilikaudelta__attachment]"]',
            resultValue: '.form-item-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta--attachment a',
          },
          value: PATH_VAHVISTETTU_TILINPAATOS,
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
  expectedDestination: "/fi/hakemus/leiriselvitys/",
}

/**
 * Basic form data for successful submit to Avus2.
 *
 * Unregistered community.
 */
const baseFormUnRegisteredCommunity_69: FormData = createFormData(
  baseForm_69,
  {
    formPages: {
      "1_hakijan_tiedot": {
        items: {
          "edit-bank-account-account-number-select": {
            role: 'select',
            value: 'use-random-value',
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
    },
  }
);

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
        'edit-jarjestimme-leireja-seuraavilla-alueilla-items-0-item-premisename',
        'edit-jarjestimme-leireja-seuraavilla-alueilla-items-0-item-postcode',
      ],
    },
    'lisatiedot_ja_liitteet': {
      items: {},
      itemsToRemove: [
        'edit-yhteison-saannot-attachment-upload',
        'edit-leiri-excel-attachment-upload',
        'edit-vahvistettu-tilinpaatos-attachment-upload',
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
    'edit-jarjestimme-leireja-seuraavilla-alueilla-items-0-item-premisename': 'Virhe sivulla 2. Leiripaikat: Tilan nimi kenttä on pakollinen.',
    'edit-jarjestimme-leireja-seuraavilla-alueilla-items-0-item-postcode': 'Virhe sivulla 2. Leiripaikat: Postinumero kenttä on pakollinen.',
    'edit-yhteison-saannot-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Yhteisön säännöt ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-leiri-excel-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Liitetiedosto kenttä on pakollinen.',
    'edit-vahvistettu-tilinpaatos-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Vahvistettu tilinpäätös (siltä kaudelta, johon leirien kustannukset kohdistuvat) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
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
    '3_talousarvio': {
      items: {},
      itemsToRemove: [
        'edit-tulo-items-0-item-label',
        'edit-meno-items-0-item-value'
      ],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: Sähköpostiosoite ääkkösiävaa ei kelpaa.',
    'edit-tulo-items-0-item-label': 'Virhe sivulla 3. Talous: Kuvaus tulosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
    'edit-meno-items-0-item-value': 'Virhe sivulla 3. Talous: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon'
  },
};

const wrongValuesUnregistered: FormDataWithRemoveOptionalProps = {
  title: 'Wrong values',
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: [],
    },
    '3_talousarvio': {
      items: {},
      itemsToRemove: [
        'edit-tulo-items-0-item-label',
        'edit-meno-items-0-item-value'
      ],
    },
  },
  expectedDestination: '',
  expectedErrors: {
    'edit-tulo-items-0-item-label': 'Virhe sivulla 3. Talous: Kuvaus tulosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
    'edit-meno-items-0-item-value': 'Virhe sivulla 3. Talous: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon'
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

/**
 * All data for registered community, keyed with id. Those do not matter.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 *
 */
const registeredCommunityApplications_69 = {
  draft: baseForm_69,
  missing_values: createFormData(baseForm_69, missingValues),
  wrong_values: createFormData(baseForm_69, wrongValues),
  // success: createFormData(baseForm_69, sendApplication),
}

/**
 * All data for unregistered community applications.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 */
const unRegisteredCommunityApplications_69 = {
  draft: baseFormUnRegisteredCommunity_69,
  missing_values: createFormData(baseFormUnRegisteredCommunity_69, missingValues),
  wrong_values: createFormData(baseFormUnRegisteredCommunity_69, wrongValuesUnregistered),
  // success: createFormData(baseFormUnRegisteredCommunity_69, sendApplication),
}

export {
  registeredCommunityApplications_69,
  unRegisteredCommunityApplications_69
}

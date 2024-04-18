import {FormData, FormDataWithRemoveOptionalProps} from "../test_data";
import {fakerFI as faker} from "@faker-js/faker"
import {PROFILE_INPUT_DATA} from "../profile_input_data";
import {ATTACHMENTS} from "../attachment_data";
import {createFormData} from "../../form_data_helpers";
import {
  viewPageFormatAddress,
  viewPageFormatFilePath,
  viewPageFormatLowerCase,
  viewPageFormatNumber
} from "../../view_page_formatters";

/**
 * Basic form data for successful submit to Avus2
 */
const baseForm_69: FormData = {
  title: 'Save as draft.',
  formSelector: 'webform-submission-leiriselvitys-form',
  formPath: '/fi/form/leiriselvitys',
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
          value: 'haloo@haloo.fi',
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
        'edit-jarjestimme-leireja-seuraavilla-alueilla': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-jarjestimme-leireja-seuraavilla-alueilla-add-submit',
              resultValue: 'edit-jarjestimme-leireja-seuraavilla-alueilla-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-jarjestimme-leireja-seuraavilla-alueilla-items-[INDEX]-item-premisename',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-jarjestimme-leireja-seuraavilla-alueilla-items-[INDEX]-item-postcode',
                  },
                  value: faker.location.zipCode(),
                },
              ],
              1: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-jarjestimme-leireja-seuraavilla-alueilla-items-[INDEX]-item-premisename',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-jarjestimme-leireja-seuraavilla-alueilla-items-[INDEX]-item-postcode',
                  },
                  value: faker.location.zipCode(),
                },
              ],
              2: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-jarjestimme-leireja-seuraavilla-alueilla-items-[INDEX]-item-premisename',
                  },
                  value: faker.lorem.words(3).toLocaleUpperCase(),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-jarjestimme-leireja-seuraavilla-alueilla-items-[INDEX]-item-postcode',
                  },
                  value: faker.location.zipCode(),
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
            value: '3_talousarvio',
          }
        },
      },
    },
    "3_talousarvio": {
      items: {
        'edit-tulo': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-tulo-add-submit',
              resultValue: 'edit-tulo-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-tulo-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(2),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-tulo-items-[INDEX]-item-value',
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
                    value: 'edit-tulo-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(2),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-tulo-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
              ],
            },
            expectedErrors: {}
          },
        },
        'edit-meno': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-meno-add-submit',
              resultValue: 'edit-meno-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-meno-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(2),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-meno-items-[INDEX]-item-value',
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
                    value: 'edit-meno-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(2),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-meno-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
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
          value: ATTACHMENTS.YHTEISON_SAANNOT,
          viewPageFormatter: viewPageFormatFilePath
        },
        'edit-leiri-excel-attachment-upload': {
          role: 'fileupload',
          selector: {
            type: 'locator',
            name: 'data-drupal-selector',
            value: '[name="files[leiri_excel_attachment]"]',
            resultValue: '.form-item-leiri-excel-attachment a',
          },
          value: ATTACHMENTS.LEIRIEXCEL,
          viewPageFormatter: viewPageFormatFilePath
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
          viewPageSelector: '.form-item-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-',
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
        'edit-jarjestimme-leireja-seuraavilla-alueilla',
      ],
    },
    'lisatiedot_ja_liitteet': {
      items: {},
      itemsToRemove: [
        'edit-yhteison-saannot-attachment-upload',
        'edit-leiri-excel-attachment-upload',
        'edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload',
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
    'edit-acting-year': 'Virhe sivulla 2. Leiripaikat: Vuosi, jota selvitys koskee kenttä on pakollinen.',
    'edit-jarjestimme-leireja-seuraavilla-alueilla-items-0-item-premisename': 'Virhe sivulla 2. Leiripaikat: Tilan nimi kenttä on pakollinen.',
    'edit-jarjestimme-leireja-seuraavilla-alueilla-items-0-item-postcode': 'Virhe sivulla 2. Leiripaikat: Postinumero kenttä on pakollinen.',
    'edit-yhteison-saannot-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Yhteisön säännöt ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
    'edit-leiri-excel-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Liitetiedosto kenttä on pakollinen.',
    'edit-vahvistettu-tilinpaatos-attachment-upload': 'Virhe sivulla 4. Lisätiedot ja liitteet: Vahvistettu tilinpäätös (siltä kaudelta, johon leirien kustannukset kohdistuvat) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.',
  },
};

const missingValuesUnregistered: FormDataWithRemoveOptionalProps = {
  title: 'Missing values',
  viewPageSkipValidation: true,
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: [
        'edit-bank-account-account-number-select',
      ],
    },
    '2_avustustiedot': {
      items: {},
      itemsToRemove: [
        'edit-jarjestimme-leireja-seuraavilla-alueilla',
      ],
    },
    'lisatiedot_ja_liitteet': {
      items: {},
      itemsToRemove: [
        'edit-yhteison-saannot-attachment-upload',
        'edit-leiri-excel-attachment-upload',
        'edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload',
      ],
    },
  },
  expectedErrors: {
    'edit-bank-account-account-number-select': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.',
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
  expectedErrors: {
    'edit-email': 'Virhe sivulla 1. Hakijan tiedot: ääkkösiävaa ei ole kelvollinen sähköpostiosoite. Täytä sähköpostiosoite muodossa user@example.com.',
    'edit-tulo-items-0-item-label': 'Virhe sivulla 3. Talous: Kuvaus tulosta ei voi olla tyhjä, kun Määrä (€) sisältää arvon',
    'edit-meno-items-0-item-value': 'Virhe sivulla 3. Talous: Määrä (€) ei voi olla tyhjä, kun Kuvaus sisältää arvon'
  },
};

const wrongValuesUnregistered: FormDataWithRemoveOptionalProps = {
  title: 'Wrong values',
  viewPageSkipValidation: true,
  formPages: {
    '1_hakijan_tiedot': {
      items: {},
      itemsToRemove: [],
    },
    '3_talousarvio': {
      items: {
        'edit-tulo': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-tulo-add-submit',
              resultValue: 'edit-tulo-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-tulo-items-[INDEX]-item-label',
                  },
                  value: '',
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-tulo-items-[INDEX]-item-value',
                  },
                  value: faker.number.int({min: 1, max: 5000}).toString(),
                  viewPageFormatter: viewPageFormatNumber,
                },
              ],
            },
            expectedErrors: {}
          },
        },
        'edit-meno': {
          role: 'multivalue',
          multi: {
            buttonSelector: {
              type: 'data-drupal-selector',
              name: 'data-drupal-selector',
              value: 'edit-meno-add-submit',
              resultValue: 'edit-meno-items-[INDEX]',
            },
            //@ts-ignore
            items: {
              0: [
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector',
                    name: 'data-drupal-selector',
                    value: 'edit-meno-items-[INDEX]-item-label',
                  },
                  value: faker.lorem.words(2),
                },
                {
                  role: 'input',
                  selector: {
                    type: 'data-drupal-selector-sequential',
                    name: 'data-drupal-selector-sequential',
                    value: 'edit-meno-items-[INDEX]-item-value',
                  },
                  value: '',
                },
              ],
            },
            expectedErrors: {}
          },
        },
      },
      itemsToRemove: [],
    },
  },
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
  success: createFormData(baseForm_69, sendApplication),
}

/**
 * All data for unregistered community applications.
 *
 * Each keyed formdata in this object will result a new test run for this form.
 */
const unRegisteredCommunityApplications_69 = {
  draft: baseFormUnRegisteredCommunity_69,
  missing_values: createFormData(baseFormUnRegisteredCommunity_69, missingValuesUnregistered),
  wrong_values: createFormData(baseFormUnRegisteredCommunity_69, wrongValuesUnregistered),
  success: createFormData(baseFormUnRegisteredCommunity_69, sendApplication),
}

export {
  registeredCommunityApplications_69,
  unRegisteredCommunityApplications_69
}

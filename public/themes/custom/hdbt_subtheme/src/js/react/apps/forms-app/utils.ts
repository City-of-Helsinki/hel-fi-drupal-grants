import { RJSFSchema, RJSFValidationError } from '@rjsf/utils';
import { FormStep } from './store';

const regex = /^.([^.]+)/;

/**
 * Return index numbers for steps that have errors in them.
 *
 * @param {Array|undefined} errors - array of RJSValidationErrors
 * @param {Map} steps - Steps from form config
 *
 * @return {Array} - Array of step indices with errors in them
 */
export const getIndicesWithErrors = (
  errors: RJSFValidationError[]|undefined,
  steps?: Map<number, FormStep>,
) => {
  if (!steps || !errors || !errors?.length) {
    return [];
  }

  const errorIndices: number[] = [];
  const propertyParentKeys: string[] = [];
  errors.forEach(error => {
    const match = error?.property?.match(regex)?.[0];

    if (match) {
      propertyParentKeys.push(match.split('.')[1]);
    }
  });
  Array.from(steps).forEach(([index, step]) => {
    if (propertyParentKeys.includes(step.id)) {
      errorIndices.push(index);
    }
  });

  return errorIndices;
};

/**
 * Key errors by page index and return them unaltered.
 *
 * @param {Array|undefined} errors - array of RJSValidationErrors
 * @param {Map} steps - Steps from form config
 *
 * @return {Array} - Array of validation errors, keyed by step index
 */
export const keyErrorsByStep = (
  errors: RJSFValidationError[]|undefined,
  steps?: Map<number, FormStep>,
) => {
  if (!steps || !errors || !errors?.length) {
    return [];
  }

  const keyedErrors: Array<[number, RJSFValidationError]> = [];

  errors.forEach(error => {
    const match = error?.property?.match(regex)?.[0];

    const matchedStep = Array.from(steps).find(([index, step]) => step.id === match?.split('.')[1]);

    if (matchedStep) {
      const [matchedIndex] = matchedStep;
      keyedErrors.push([matchedIndex, error]);
    }
  });

  return keyedErrors;
};

/**
 * Checks that server response is in valid form response format.
 *  @todo implement actual check when server implementation is finalized.
 *
 * @param {Object} data - Server response
 *
 * @return {[boolean, string|null]} - [isValid, message]
 */
export const isValidFormResponse = (data: Object): [boolean, string|undefined] => {
  return [true, undefined];
};


const privatePersonSettings = [
  {
    applicant_info: {
      title: 'Omat yhteystiedot',
      type: 'object',
      $ref: '#/definitions/applicant_info',
    }
  },
  {
    applicant_info: {
      type: 'object',
      properties: {
        applicant_address_data: {
          title: 'Osoite',
          type: 'object',
          properties: {
            address: {
              title: 'Henkilökohtainen osoite',
              type: 'object',
              properties: {
                address_name: {
                  title: 'Katuosoite',
                  type: 'string',
                  minLength: 1,
                },
                postal_code: {
                  title: 'Postinumero',
                  type: 'string',
                  minLength: 5,
                },
                post_area: {
                  title: 'Toimipaikka',
                  type: 'string',
                  minLength: 1
                },
              },
            },
          },
        },
        applicant_phone: {
          title: 'Puhelinnumero',
          type: 'object',
          properties: {
            title: 'Henkilökohtainen puhelinnumero',
            type: 'string',
            minLength: 1,
          }
        }
      },
    },
  },
];

const communitySettings = [
  {
    title: 'Yhteisö, jolle haetaan avustusta',
    type: 'object',
    '$ref': '#/definitions/applicant_info',
  },
  {
    type: 'object',
    properties: {
      applicant_email: {
        title: 'Sähköpostiosoite',
        description: 'Ilmoita tässä sellainen yhteisön sähköpostiosoite, jota luetaan aktiivisesti. Sähköpostiin lähetetään avustushakemukseen liittyviä yhteydenottoja esim. lisäselvitys- ja täydennyspyyntöjä.',
        type: 'object',
        properties: {
          email: {
            title: 'Sähköpostiosoite',
            type: 'string',
            format: 'email'
          }
        },
        required: ['email']
      },
      contact_person: {
        title: 'Hakemuksen yhteyshenkilö',
        type: 'object',
        properties: {
          contact_person: {
            title: 'Yhteyshenkilö',
            type: 'string',
            default: ''
          },
          contact_person_phone_number: {
            title: 'Puhelinnumero',
            type: 'string',
            default: ''
          }
        }
      },
      community_address: {
        title: 'Osoite',
        type: 'object',
        properties: {
          community_address: {
            title: 'Valitse osoite',
            type: 'string',
            enum: ['Mannerheimintie 1, 00100, Helsinki (tää tulee sieltä käyttäjän tiedoista)']
          }
        }
      },
      bank_account: {
        title: 'Tilinumero',
        type: 'object',
        properties: {
          bank_account: {
            title: 'Valitse tilinumero',
            type: 'string',
            enum: ['FI4950009420028730 (käyttäjän tietoja)']
          }
        }
      },
      community_officials: {
        title: 'Toiminnasta vastaavat henkilöt',
        type: 'object',
        properties: {
          community_officials: {
            title: 'Valitse toiminnasta vastaava henkilö',
            type: 'string',
            enum: ['Teppo Testaaja (Yhteyshenkilö) (käyttäjän tietoja)']
          }
        }
      }
    },
    required: ['applicant_email']
  }
];

/**
 * Add static applicant info step to form schema.
 *
 * @param {Object} schema - Form schema
 * @param {Object} grantsProfile - Grants profile
 *
 * @return {Object} - Resulting form schema
 */
export const addApplicantInfoStep = (schema: RJSFSchema, grantsProfile: Array<undefined>|{business_id: string}): RJSFSchema => {
  const { definitions, properties } = schema;
  const [rootProperty, definition] = Array.isArray(grantsProfile) ? privatePersonSettings : communitySettings;

  let transformedSchema = {...schema};
  transformedSchema.properties = {
    // @ts-ignore
    applicant_info: rootProperty,
    ...properties
  };
  transformedSchema.definitions = {
    // @ts-ignore
    applicant_info: definition,
    ...definitions,
  };


  return transformedSchema;
};

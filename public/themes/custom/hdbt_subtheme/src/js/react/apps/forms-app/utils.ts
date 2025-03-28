import { RJSFSchema, RJSFValidationError, UiSchema } from '@rjsf/utils';
import { FormStep } from './store';
import { communitySettings } from './formConstants';

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
 * @return {Array} - [isValid, message]
 */
export const isValidFormResponse = (data: Object): [boolean, string|undefined] => [true, undefined];

/**
 * Add static applicant info step to form schema.
 *
 * @param {Object} schema - Form schema
 * @param {Object} uiSchema - Form Ui Schema
 * @param {Object} grantsProfile - Grants profile
 *
 * @return {Array} - Resulting forma and ui schemas
 */
export const addApplicantInfoStep = (
  schema: RJSFSchema,
  uiSchema: UiSchema,
  grantsProfile: Array<undefined>|{business_id: string}
): [RJSFSchema, UiSchema] => {
  const { definitions, properties } = schema;
  const [rootProperty, definition, uiSchemaAdditions] = communitySettings;

  const transformedSchema: RJSFSchema = {
    ...schema,
    definitions: {
      applicant_info: definition,
      ...definitions,
    },
    properties: {
      applicant_info: rootProperty,
      ...properties,
    },
  };

  const transformedUiSchema: UiSchema = {
    ...uiSchema,
    ...uiSchemaAdditions,
  };

  return [transformedSchema, transformedUiSchema];
};

/**
 * Get nested property from object with dot notation
 *
 * @param {object} obj - Object to traverse
 * @param {string} path - Point to a nested property in string format
 * @return {any} - Value of nested property or undefined
 */
export const getNestedSchemaProperty = (obj: any, path: string) => {
  const properties = path.split('.').slice(1);
  let current = obj;

  properties.forEach((property, index) => {
    if (!Object.prototype.hasOwnProperty.call(current, property)) {
      return undefined;
    }
    if (index === properties.length - 1) {
      current = current[property];
    }
    else {
      current = current[property]?.properties ?? current[property];
    }
  });

  return current;
}

/**
 * Set nested object prroperty with dot notation.
 *
 * @param {object} obj - object to manipulate
 * @param {string} path - path to transform
 * @param {object|string|array} value - value to set
 *
 * @return {void}
 */
export const setNestedProperty = (obj: any, path: string, value: any) => {
  const properties = path.split('.').slice(1);
  let current = obj;

  properties.forEach((property, index) => {
    if (index === properties.length - 1) {
      current[property] = value;
    } else {
      if (!Object.prototype.hasOwnProperty.call(current, property)) {
        current[property] = {};
      }
      current = current[property];
    }
  });
}

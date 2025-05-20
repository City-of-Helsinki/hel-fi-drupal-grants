import { ErrorObject } from 'ajv';

/**
 * Format a required field error message.
 *
 * @param {ErrorObject} error - The error object containing validation details.
 * @return {string} - Translated error message indicating the required field.
 */
const formatRequiredError = (error: ErrorObject) => {
  const missingProperty = Array.isArray(error.schema) && error.schema[0];

  if (!missingProperty || !error.parentSchema?.properties?.[missingProperty]) {
    return Drupal.t('Field is required', {}, {context: 'Grants application: Validation'});
  }

  const { title } = error.parentSchema.properties[missingProperty];

  return Drupal.t('@field field is required', {
    '@field': title,
  }, {
    context: 'Grants application: Validation',
  });
};


/**
 * Localize validation errors.
 *
 * @param {ErrorObject[]|null} errors - Ajv validation errors
 * 
 * @return {ErrorObject[]}
 *   Same array of errors, with error messages translated to Drupal t() where possible.
 */
export const localizeErrors = (errors?: null | ErrorObject[]) => {
  if (!(errors && errors.length)) {
    return [];
  }

  errors.forEach((error) => {
    let outMessage: string|null|undefined;

    switch (error.keyword) {
      case 'required': {
        outMessage = formatRequiredError(error);
        break;
      }
      default:
        outMessage = error.message;
    }

    error.message = outMessage;
  });
};

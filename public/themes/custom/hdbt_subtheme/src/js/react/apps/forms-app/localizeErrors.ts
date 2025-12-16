import type { ErrorObject } from 'ajv';

/**
 * Localize the "minLength" error from AJV.
 *
 * If the minimum length is 1, use the "required" error message.
 *
 * @param {ErrorObject} error
 *   The error object.
 *
 * @return {string}
 *   The localized error message.
 */
const formatMinLengthError = (error: ErrorObject) => {
  const {
    params: { limit },
    parentSchema,
  } = error;

  // RJSF accepts empty strings as valid input for string fields (since this is valid according JSONSchema specification)
  if (limit === 1 && parentSchema) {
    return Drupal.t(
      '@field field is required',
      { '@field': parentSchema.title },
      { context: 'Grants application: Validation' },
    );
  }

  return Drupal.t(
    '@field field must be at least @limit characters',
    { '@field': parentSchema?.title, '@limit': limit },
    { context: 'Grants application: Validation' },
  );
};

/**
 * Format a required field error message.
 *
 * @param {ErrorObject} error - The error object containing validation details.
 * @return {string} - Translated error message indicating the required field.
 */
const formatRequiredError = (error: ErrorObject) => {
  const missingProperty = Array.isArray(error.schema) && error.schema[0];

  if (!missingProperty || !error.parentSchema?.properties?.[missingProperty]) {
    return Drupal.t(
      'Field is required',
      {},
      { context: 'Grants application: Validation' },
    );
  }

  const { title } = error.parentSchema.properties[missingProperty];

  return Drupal.t(
    '@field field is required',
    { '@field': title },
    { context: 'Grants application: Validation' },
  );
};

/**
 * @todo extend this to support other patterns
 *
 * @param {ErrorObject} error - The error object containing validation details.
 *
 * @return {string} - Translated error message indicating the required field.
 */
const formatPatternError = (error: ErrorObject) => {
  const { data } = error;

  if (!data || data === '') {
    return formatRequiredError(error);
  }

  return Drupal.t(
    'The email address @mail is not valid. Use the format user@example.com.',
    { '@mail': data },
    { context: 'Grants application: Validation' },
  );
};

/**
 * @todo extends to support other types
 *
 * @param {ErrorObject} error - The error object containing validation details.
 *
 * @return {string} - Translated error message indicating the required field.
 */
const formatTypeError = (error: ErrorObject) => {
  const { schema } = error;

  if (schema === 'integer') {
    return Drupal.t(
      'The value must be an integer.',
      {},
      { context: 'Grants application: Validation' },
    );
  }

  return Drupal.t(
    'Value is of incorrect type.',
    {},
    { context: 'Grants application: Validation' },
  );
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
  if (!errors?.length) {
    return [];
  }

  errors.forEach((error) => {
    let outMessage: string | null | undefined;

    switch (error.keyword) {
      case 'format': {
        outMessage = formatPatternError(error);
        break;
      }
      case 'minLength': {
        outMessage = formatMinLengthError(error);
        break;
      }
      case 'required': {
        outMessage = formatRequiredError(error);
        break;
      }
      case 'type': {
        outMessage = formatTypeError(error);
        break;
      }
      default:
        outMessage = error.message;
    }

    error.message = outMessage;
  });
};

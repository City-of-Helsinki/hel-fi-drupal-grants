import type { ErrorObject } from 'ajv';

/**
 * Localize the "minItems" error from AJV.
 *
 * @param {ErrorObject} error
 *  The error object.
 *
 * @return {string}
 *   The localized error message.
 */
const formatMinItemsError = (error: ErrorObject) => {
  const {
    params: { limit },
    parentSchema,
  } = error;

  return Drupal.t(
    'You must insert at least @limit value for field !field',
    { '!field': parentSchema?.title || '', '@limit': limit.toString() },
    { context: 'Grants application: Validation' },
  );
};

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
      '!field field is required.',
      { '!field': parentSchema.title },
      { context: 'Grants application: Validation' },
    );
  }

  return Drupal.t(
    '!field field must be at least @limit characters.',
    { '!field': parentSchema?.title, '@limit': limit },
    { context: 'Grants application: Validation' },
  );
};

/**
 * Localize the "maxLength" error from AJV.
 *
 * The inputs cap typing and pasting, but a value can still arrive over the
 * limit from a restored draft or a profile prefill.
 *
 * @param {ErrorObject} error
 *   The error object.
 *
 * @return {string}
 *   The localized error message.
 */
const formatMaxLengthError = (error: ErrorObject) => {
  const {
    params: { limit },
    parentSchema,
  } = error;

  return Drupal.t(
    '!field field must be at most @limit characters.',
    { '!field': parentSchema?.title, '@limit': limit },
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
  const missingProperty = error.params?.missingProperty?.toString().replace(/^'|'$/g, '');

  if (!missingProperty || !error.parentSchema?.properties?.[missingProperty]) {
    return Drupal.t('Field is required', {}, { context: 'Grants application: Validation' });
  }

  const { title } = error.parentSchema.properties[missingProperty];

  return Drupal.t('!field field is required.', { '!field': title }, { context: 'Grants application: Validation' });
};

/**
 * @todo extend this to support other patterns
 *
 * @param {ErrorObject} error - The error object containing validation details.
 *
 * @return {string} - Translated error message indicating the required field.
 */
const formatPatternError = (error: ErrorObject) => {
  const {
    data,
    params: { format },
  } = error;

  if (!data || data === '') {
    return formatRequiredError(error);
  }

  if (format === 'year') {
    return Drupal.t(
      '!field field must be a year written with four digits.',
      { '!field': error.parentSchema?.title },
      { context: 'Grants application: Validation' },
    );
  }

  if (format === 'email') {
    return Drupal.t(
      'The email address @mail is not valid. Use the format user@example.com.',
      { '@mail': data },
      { context: 'Grants application: Validation' },
    );
  }

  return Drupal.t('Value is of incorrect type.', {}, { context: 'Grants application: Validation' });
};

/**
 * Work out the years a pattern accepts.
 *
 * @param {string} pattern - The pattern from the field's schema
 *
 * @return {Array|undefined} - The lowest and highest accepted year
 */
const getYearBounds = (pattern: string): [number, number] | undefined => {
  try {
    const expression = new RegExp(pattern);
    const accepted = [];

    for (let year = 1000; year <= 9999; year++) {
      if (expression.test(year.toString())) {
        accepted.push(year);
      }
    }

    return accepted.length ? [accepted[0], accepted[accepted.length - 1]] : undefined;
  } catch {
    return undefined;
  }
};

/**
 * Localize the "pattern" error from AJV.
 *
 * @param {ErrorObject} error - The error object containing validation details.
 *
 * @return {string} - The localized error message.
 */
const formatPatternKeywordError = (error: ErrorObject) => {
  const { data, parentSchema } = error;

  if (!data || data === '') {
    return formatRequiredError(error);
  }

  if (parentSchema?.format === 'year' && typeof parentSchema.pattern === 'string') {
    const bounds = getYearBounds(parentSchema.pattern);

    if (bounds) {
      return Drupal.t(
        '!field field must be a year between @min and @max.',
        { '!field': parentSchema.title, '@min': bounds[0], '@max': bounds[1] },
        { context: 'Grants application: Validation' },
      );
    }
  }

  return Drupal.t(
    '!field field is not in the correct format.',
    { '!field': parentSchema?.title },
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
    return Drupal.t('The value must be an integer.', {}, { context: 'Grants application: Validation' });
  }

  if (schema === 'number') {
    return Drupal.t('The value must be a number.', {}, { context: 'Grants application: Validation' });
  }

  return Drupal.t('Value is of incorrect type.', {}, { context: 'Grants application: Validation' });
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
    let outMessage: string | undefined;

    switch (error.keyword) {
      case 'format': {
        outMessage = formatPatternError(error);
        break;
      }
      case 'maxLength': {
        outMessage = formatMaxLengthError(error);
        break;
      }
      case 'pattern': {
        outMessage = formatPatternKeywordError(error);
        break;
      }
      case 'minItems': {
        outMessage = formatMinItemsError(error);
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

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
    'You must insert at least @limit value for field @field',
    { '@field': parentSchema?.title || '', '@limit': limit.toString() },
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
      '@field field is required.',
      { '@field': parentSchema.title },
      { context: 'Grants application: Validation' },
    );
  }

  return Drupal.t(
    '@field field must be at least @limit characters.',
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
  const missingProperty = error.params?.missingProperty?.toString().replace(/^'|'$/g, '');

  if (!missingProperty || !error.parentSchema?.properties?.[missingProperty]) {
    return Drupal.t('Field is required', {}, { context: 'Grants application: Validation' });
  }

  const { title } = error.parentSchema.properties[missingProperty];

  if (!title) {
    return Drupal.t('Field is required', {}, { context: 'Grants application: Validation' });
  }

  return Drupal.t('@field field is required.', { '@field': title }, { context: 'Grants application: Validation' });
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
    return Drupal.t('The value must be an integer.', {}, { context: 'Grants application: Validation' });
  }

  return Drupal.t('Value is of incorrect type.', {}, { context: 'Grants application: Validation' });
};

/**
 * Expand required errors on missing object-type properties into errors on
 * their required sub-fields, so RJSF can route them to individual widgets.
 *
 * When a conditional section is absent from formData, AJV fires a `required`
 * error at the parent (step) level. RJSF routes that to the section div and
 * the individual fields inside never receive `rawErrors`. This function walks
 * the missing property's schema and generates synthetic `required` errors for
 * each required sub-field (and recursively for nested objects), up to 4 levels.
 */
const expandRequiredObjectErrors = (errors: ErrorObject[]): void => {
  // RJSF wraps params.missingProperty with quotes before calling localizer, then strips them after.
  // New errors must follow the same convention so RJSF's post-localizer strip doesn't corrupt them.
  const stripQuotes = (v: string) => v.replace(/^'|'$/g, '');
  const existingKeys = new Set(
    errors.map((e) => `${e.instancePath}|${stripQuotes(e.params?.missingProperty?.toString() ?? '')}`),
  );
  const toAdd: ErrorObject[] = [];

  const expand = (error: ErrorObject, depth: number): void => {
    if (depth > 3) return;
    const missingPropName = stripQuotes(error.params?.missingProperty?.toString() ?? '');
    if (!missingPropName) return;
    const missingPropSchema = (error.parentSchema as any)?.properties?.[missingPropName];
    if (
      !missingPropSchema ||
      missingPropSchema.type !== 'object' ||
      !Array.isArray(missingPropSchema.required) ||
      missingPropSchema.required.length === 0
    ) {
      return;
    }
    const newInstancePath = `${error.instancePath}/${missingPropName}`;
    (missingPropSchema.required as string[]).forEach((subField) => {
      const key = `${newInstancePath}|${subField}`;
      if (existingKeys.has(key)) return;
      existingKeys.add(key);
      const newError = {
        keyword: 'required',
        instancePath: newInstancePath,
        schemaPath: error.schemaPath,
        // Wrap with quotes so RJSF's post-localizer .slice(1,-1) yields the correct name.
        params: { missingProperty: `'${subField}'` },
        message: `must have required property '${subField}'`,
        parentSchema: missingPropSchema,
        schema: missingPropSchema.required,
        data: {},
      } as unknown as ErrorObject;
      toAdd.push(newError);
      expand(newError, depth + 1);
    });
  };

  errors.forEach((error) => {
    if (error.keyword === 'required') {
      expand(error, 0);
    }
  });
  errors.push(...toAdd);
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

  expandRequiredObjectErrors(errors);

  errors.forEach((error) => {
    let outMessage: string | null | undefined;

    switch (error.keyword) {
      case 'format': {
        outMessage = formatPatternError(error);
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

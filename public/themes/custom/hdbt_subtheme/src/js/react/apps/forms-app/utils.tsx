import type { RJSFSchema, RJSFValidationError, UiSchema } from '@rjsf/utils';
import { Tooltip } from 'hds-react';
import { htmlToReact } from '@/react/common/helpers/htmlToReact';
import { UserType } from './enum/UserType';
import { communitySettings, privatePersonSettings, unregisteredCommunitySettings } from './formConstants';
import type { FormStep } from './store';
import type { ResponseData } from './types/Data';
import type { RJSFFormData } from './types/RJSFFormData';

type SchemaNode = {
  type?: string;
  title?: string;
  $ref?: string;
  required?: string[];
  properties?: Record<string, SchemaNode>;
  allOf?: Array<{ if?: SchemaNode; then?: SchemaNode; else?: SchemaNode }>;
  definitions?: Record<string, SchemaNode>;
};

const regex = /^.([^.]+)/;

/**
 * Return index numbers for steps that have errors in them.
 *
 * @param {Array|undefined} errors - array of RJSValidationErrors
 * @param {Map} steps - Steps from form config
 *
 * @return {Array} - Array of step indices with errors in them
 */
export const getIndicesWithErrors = (errors: RJSFValidationError[] | undefined, steps?: Map<number, FormStep>) => {
  if (!steps || !errors?.length) {
    return [];
  }

  const errorIndices: number[] = [];
  const propertyParentKeys: string[] = [];
  errors.forEach((error) => {
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
 * Resolve the schema for a property at the given path, merging allOf/then schemas.
 *
 * path starts after the root, e.g. ['step_id', 'section_id', 'fieldset_id']
 * The step level resolves $ref from schema.definitions.
 * Subsequent levels merge base properties with allOf/then properties.
 */
const resolveFieldSchema = (rootSchema: RJSFSchema, path: string[]): SchemaNode | null => {
  if (path.length === 0) return null;

  const [stepId, ...rest] = path;
  const stepProp = (rootSchema.properties as Record<string, SchemaNode> | undefined)?.[stepId];

  if (!stepProp || typeof stepProp !== 'object') {
    return null;
  }

  const refName = stepProp?.$ref?.replace('#/definitions/', '');
  const stepDef = refName ? (rootSchema.definitions as Record<string, SchemaNode> | undefined)?.[refName] : stepProp;

  if (!stepDef) return null;
  if (rest.length === 0) return stepDef;

  return resolveNestedSchema(stepDef, rest);
};

const resolveNestedSchema = (schema: SchemaNode | null | undefined, path: string[]): SchemaNode | null => {
  if (!schema || path.length === 0) return schema ?? null;

  const [key, ...rest] = path;

  // Start with base property schema
  let merged: SchemaNode | null = schema.properties?.[key] ? { ...schema.properties[key] } : null;

  // Merge in any allOf/then schemas for this key
  for (const allOfItem of schema.allOf ?? []) {
    const thenField = allOfItem.then?.properties?.[key];
    if (thenField) {
      merged = merged
        ? {
            ...merged,
            ...thenField,
            required: [...(merged.required ?? []), ...(thenField.required ?? [])],
            properties: { ...(merged.properties ?? {}), ...(thenField.properties ?? {}) },
          }
        : { ...thenField };
    }
  }

  if (!merged) return null;
  if (rest.length === 0) return merged;

  return resolveNestedSchema(merged, rest);
};

/**
 * Recursively generate leaf-level required errors for an absent object schema.
 * Object-typed required fields are recursed into rather than generating object-level errors,
 * so only actual input fields (leaf nodes) receive rawErrors and show red borders.
 */
const expandLeafRequiredErrors = (
  objectSchema: SchemaNode,
  pathPrefix: string,
  expanded: RJSFValidationError[],
  schemaPath: string,
): void => {
  for (const fieldId of objectSchema.required ?? []) {
    const fieldSchema = objectSchema.properties?.[fieldId];

    if (fieldSchema?.type === 'object') {
      // Recurse into nested object (fieldset) rather than adding an object-level error
      expandLeafRequiredErrors(fieldSchema, `${pathPrefix}.${fieldId}`, expanded, schemaPath);
    } else {
      const fieldTitle = fieldSchema?.title;
      const message = fieldTitle
        ? Drupal.t('!field field is required.', { '!field': fieldTitle }, { context: 'Grants application: Validation' })
        : Drupal.t('Field is required', {}, { context: 'Grants application: Validation' });

      expanded.push({
        property: `${pathPrefix}.${fieldId}`,
        message,
        stack: `${pathPrefix}.${fieldId} ${message}`,
        schemaPath,
        params: { missingProperty: fieldId },
        name: 'required',
      } as RJSFValidationError);
    }
  }
};

/**
 * Expand required errors for absent objects to individual leaf field errors.
 *
 * When a section or fieldset object is absent from form data and is required
 * (e.g. via allOf/then), AJV only generates an object-level required error.
 * This function expands such errors to leaf-level field errors so input fields
 * can display red border highlighting and individual error messages.
 *
 * Handles any nesting depth: missing sections (depth 2), missing fieldsets within
 * sections (depth 3), and deeper.
 *
 * @param {Array} errors - RJSF validation errors
 * @param {RJSFSchema} schema - Form schema
 * @param {any} formData - Current form data
 *
 * @return {Array} - Errors with absent-object required errors expanded to leaf field errors
 */
export const expandConditionalRequiredErrors = (
  errors: RJSFValidationError[],
  schema: RJSFSchema,
  formData: RJSFFormData,
): RJSFValidationError[] => {
  const expanded: RJSFValidationError[] = [];

  for (const error of errors) {
    expanded.push(error);

    if (error.name !== 'required' || !error.property) {
      continue;
    }

    const parts = error.property.split('.').filter(Boolean);
    if (parts.length < 2) {
      continue;
    }

    // Check if the missing property is absent from form data
    const parentParts = parts.slice(0, -1);
    const missingFieldId = parts[parts.length - 1];
    const parentData = parentParts.reduce<Record<string, unknown> | undefined>(
      (data, key) =>
        data && typeof data === 'object' ? (data[key] as Record<string, unknown> | undefined) : undefined,
      formData,
    );
    const missingFieldData = parentData?.[missingFieldId];

    // Only expand if absent — if present (even as {}), AJV already generates field errors
    if (missingFieldData !== undefined && missingFieldData !== null) {
      continue;
    }

    // Get the merged schema for the missing field (resolves $ref and merges allOf/then)
    const missingFieldSchema = resolveFieldSchema(schema, parts);
    if (!missingFieldSchema || missingFieldSchema.type !== 'object') {
      continue;
    }

    // Recursively expand to leaf required field errors
    expandLeafRequiredErrors(missingFieldSchema, error.property, expanded, error.schemaPath ?? '');
  }

  return expanded;
};

/**
 * Key errors by page index and return them unaltered.
 *
 * @param {Array|undefined} errors - array of RJSValidationErrors
 * @param {Map} steps - Steps from form config
 *
 * @return {Array} - Array of validation errors, keyed by step index
 */
export const keyErrorsByStep = (errors: RJSFValidationError[] | undefined, steps?: Map<number, FormStep>) => {
  if (!steps || !errors?.length) {
    return [];
  }

  const keyedErrors: Array<[number, RJSFValidationError]> = [];
  const stepsArray = Array.from(steps);

  errors.forEach((error) => {
    const match = error?.property?.match(regex)?.[0];

    const matchedStep = stepsArray.find(([_index, step]) => step.id === match?.split('.')[1]);

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
 * @param {Object} _data - Server response
 *
 * @return {Array} - [isValid, message]
 */
export const isValidFormResponse = (_data: ResponseData): [boolean, string | undefined] => [true, undefined];

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
  userType: string | undefined,
): [RJSFSchema, UiSchema] => {
  const { definitions, properties } = schema;

  // biome-ignore lint/suspicious/noExplicitAny: Refactor this
  let rootProperty: any;
  // biome-ignore lint/suspicious/noExplicitAny: Refactor this
  let definition: any;
  let uiSchemaAdditions: UiSchema;

  if (userType === UserType.PRIVATE_PERSON) {
    [rootProperty, definition, uiSchemaAdditions] = privatePersonSettings;
  } else if (userType === UserType.UNREGISTERED_COMMUNITY) {
    [rootProperty, definition, uiSchemaAdditions] = unregisteredCommunitySettings;
  } else {
    [rootProperty, definition, uiSchemaAdditions] = communitySettings;
  }

  const transformedSchema: RJSFSchema = {
    ...schema,
    definitions: { applicant_info: definition, ...definitions },
    properties: { applicant_info: rootProperty, ...properties },
  };

  const transformedUiSchema: UiSchema = { ...uiSchema, ...uiSchemaAdditions };

  return [transformedSchema, transformedUiSchema];
};

/**
 * Get nested property from object with dot notation
 *
 * @param {object} obj - Object to traverse
 * @param {string} path - Point to a nested property in string format
 * @param {boolean} replaceStep - Whether to replace _step occurrences in path
 *
 * @return {any} - Value of nested property or undefined
 */
export const getNestedSchemaProperty = (obj: RJSFSchema, path: string) => {
  const properties = path.split('.').slice(1);
  let current: RJSFSchema | undefined = obj;

  properties.forEach((property, index) => {
    const propertyName = property.toString();
    const hasProperty = current && Object.hasOwn(current, propertyName);
    if (hasProperty && index === properties.length - 1) {
      current = current?.[propertyName];
    } else if (hasProperty) {
      current = current?.[propertyName]?.properties ?? current?.[propertyName];
    } else {
      current = undefined;
    }
  });

  return current;
};

/**
 * Set nested object prroperty with dot notation.
 *
 * @param {object} obj - object to manipulate
 * @param {string} path - path to transform
 * @param {object|string|array} value - value to set
 *
 * @return {void}
 */
export const setNestedProperty = (obj: Record<string, unknown>, path: string, value: unknown) => {
  const properties = path.split('.').slice(1);
  let current: Record<string, unknown> = obj;

  properties.forEach((property, index) => {
    if (index === properties.length - 1) {
      current[property] = value;
    } else {
      if (!Object.hasOwn(current, property)) {
        current[property] = {};
      }
      current = current[property] as Record<string, unknown>;
    }
  });
};

/**
 * Return a deep clone of form data with the given paths removed.
 *
 * Used to drop synthetic helper fields (flagged with misc:exclude-from-submit)
 * from the payload sent to the backend. Paths are root-relative dot notation
 * with no leading dot, e.g. 'step.section.field'.
 *
 * @param {any} data - Form data
 * @param {string[]} paths - Paths to remove
 *
 * @return {any} - Cloned data without the listed paths
 */
// biome-ignore lint/suspicious/noExplicitAny: Refactor this
export const stripExcludedFields = (data: any, paths: string[]): any => {
  if (!paths.length) {
    return data;
  }

  const clone = structuredClone(data);

  paths.forEach((path) => {
    const keys = path.split('.');
    const last = keys.pop();

    if (!last) {
      return;
    }

    const parent = keys.reduce((acc, key) => (acc == null ? acc : acc[key]), clone);

    if (parent && typeof parent === 'object') {
      delete parent[last];
    }
  });

  return clone;
};

/**
 * Finds field of a given type in the form schema.
 *
 * @param {any} element - current element
 * @param {string } type - field type
 * @param { string } prefix - current form element path in dot notation
 *
 * @yields {string} - form element path
 */
export function* findFieldsOfType(element: unknown, type: string, prefix: string = ''): IterableIterator<string> {
  const isObject = typeof element === 'object' && !Array.isArray(element) && element !== null;

  if (isObject && (element as Record<string, unknown>)['ui:field'] === type) {
    yield prefix;
  } else if (isObject) {
    // Functional loops mess mess up generator function, so use for - of loop here.
    // eslint-disable-next-line no-restricted-syntax
    for (const [key, value] of Object.entries(element)) {
      yield* findFieldsOfType(value, type, prefix.length ? `${prefix}.${key}` : key);
    }
  }
}

/**
 * Finds fields that have a specific uiSchema option set to true.
 *
 * @param {any} element - current element
 * @param {string} option - uiSchema option key to look for
 * @param {string} prefix - current form element path in dot notation
 *
 * @yields {string} - form element path
 */
export function* findFieldsWithOption(element: unknown, option: string, prefix: string = ''): IterableIterator<string> {
  const isObject = typeof element === 'object' && !Array.isArray(element) && element !== null;

  if (isObject && (element as Record<string, unknown>)[option]) {
    yield prefix;
  } else if (isObject) {
    // Functional loops mess up generator function, so use for - of loop here.
    // eslint-disable-next-line no-restricted-syntax
    for (const [key, value] of Object.entries(element)) {
      yield* findFieldsWithOption(value, option, prefix.length ? `${prefix}.${key}` : key);
    }
  }
}

/**
 * Transform raw errors to a more readable format.
 *
 * @param {array|undefned} rawErrors - Errors from RJSF form
 * @return {string} - Resulting error messagea
 */
export const formatErrors = (rawErrors: string[] | undefined) => {
  if (!rawErrors) {
    return undefined;
  }

  return rawErrors.join('\n');
};

/**
 * Get the total sum of subvention fields from the form data.
 *
 * @param {object} formData - Form data
 * @param {array} subventionFields - Array of subvention field paths
 * @return {number} - Total sum
 */
export const getSubventionSum = (formData: RJSFFormData, subventionFields: string[]) =>
  subventionFields.reduce((total, field) => {
    const values = getNestedSchemaProperty(formData, field);
    let totalNumericValue = Number(String(total).replace(',', '.'));

    if (values?.length) {
      Object.entries(values).forEach(([, curr]) => {
        const amount = Number(String(curr[1].value).replace(',', '.'));

        if (!Number.isNaN(amount)) {
          totalNumericValue += amount;
        }
      });
    }

    return totalNumericValue.toString().replace('.', ',');
  }, '0');

/**
 * Check if the form is in draft mode.
 *
 * @return {boolean} - Whether form is a draft
 */
export const isDraft = () => drupalSettings.grants_react_form.use_draft;

export const ALLOWED_HTML_TAGS = ['p', 'ul', 'ol', 'li', 'strong', 'a'];

/**
 * Get the tooltip component.
 *
 * @param {object} uiSchema - uiSchema
 * @return {React.ReactNode} - Tooltip component
 */
export const getTooltip = (uiSchema: UiSchema | undefined) => {
  if (!uiSchema?.['ui:options']?.tooltipText) {
    return undefined;
  }

  return (
    <Tooltip
      className='hdbt-react-form__tooltip-container'
      buttonLabel={uiSchema?.['ui:options']?.tooltipButtonLabel?.toString()}
      tooltipLabel={uiSchema?.['ui:options']?.tooltipLabel?.toString()}
    >
      {htmlToReact(uiSchema['ui:options'].tooltipText.toString(), ALLOWED_HTML_TAGS)}
    </Tooltip>
  );
};

export const sanitizeNumericInput = (
  value: string,
  type: 'integer' | 'decimal-number' | 'phone' = 'integer',
  lastInput: string,
): string => {
  let pattern: RegExp;

  switch (type) {
    case 'integer':
      pattern = /[^0-9]/g;
      break;
    case 'decimal-number':
      pattern = /[^0-9,]/g;
      break;
    case 'phone':
      pattern = /[^0-9 +()]/g;
      break;
    default:
      pattern = /[^0-9,]/g;
  }

  // Start with the
  value = value.replace(pattern, '').replace(/ {2,}/g, ' ');

  // Prevent multiple commas by replacing the last one.
  if (lastInput === ',') {
    const commaCount = value.match(/[,]/g)?.length ?? 0;
    if (commaCount > 1) {
      const position = value.lastIndexOf(',');
      const firstPart = value.substring(0, position);
      const lastPart = value.substring(position + 1);
      value = firstPart + lastPart;

      // Remove characters after comma until there is max 2.
      // If used decides to put comma in the middle of large number.
      while (value.match(/,[0-9]{3}/)) {
        value = value.substring(0, value.length - 1);
      }
    }
  }

  // Prevent more than two numbers after comma.
  if (value.match(/,[0-9]{3}/)) {
    value = value.substring(0, value.length - 1);
  }

  return value; // Double comma.
};

export const numberIsTooLarge = (value: string): boolean => {
  const numericValue = Number(value.replace(',', '.'));
  return Math.abs(numericValue) >= 1e21;
};

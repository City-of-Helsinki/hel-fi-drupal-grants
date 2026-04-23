// biome-ignore-all lint/suspicious/useIterableCallbackReturn: @todo UHF-12501
// biome-ignore-all lint/suspicious/noExplicitAny: @todo UHF-12501
// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
// biome-ignore-all lint/suspicious/noPrototypeBuiltins: @todo UHF-12501
// biome-ignore-all lint/complexity/noBannedTypes: @todo UHF-12501
import type { RJSFSchema, RJSFValidationError, UiSchema } from '@rjsf/utils';
import type { FormStep } from './store';
import { communitySettings } from './formConstants';
import { Tooltip } from 'hds-react';
import parse, { type DOMNode, type Element, domToReact } from 'html-react-parser';

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
  if (!steps || !errors || !errors?.length) {
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
const resolveFieldSchema = (rootSchema: RJSFSchema, path: string[]): any => {
  if (path.length === 0) return null;

  const [stepId, ...rest] = path;
  const stepProp = rootSchema.properties?.[stepId] as any;
  const refName = stepProp?.['$ref']?.replace('#/definitions/', '');
  const stepDef = (refName ? (rootSchema.definitions as any)?.[refName] : stepProp) as any;

  if (!stepDef) return null;
  if (rest.length === 0) return stepDef;

  return resolveNestedSchema(stepDef, rest);
};

const resolveNestedSchema = (schema: any, path: string[]): any => {
  if (!schema || path.length === 0) return schema;

  const [key, ...rest] = path;

  // Start with base property schema
  let merged: any = schema?.properties?.[key] ? { ...schema.properties[key] } : null;

  // Merge in any allOf/then schemas for this key
  for (const allOfItem of schema?.allOf ?? []) {
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
  objectSchema: any,
  pathPrefix: string,
  expanded: RJSFValidationError[],
  schemaPath: string,
): void => {
  for (const fieldId of objectSchema?.required ?? []) {
    const fieldSchema = objectSchema?.properties?.[fieldId];

    if (fieldSchema?.type === 'object') {
      // Recurse into nested object (fieldset) rather than adding an object-level error
      expandLeafRequiredErrors(fieldSchema, `${pathPrefix}.${fieldId}`, expanded, schemaPath);
    } else {
      const fieldTitle = fieldSchema?.title;
      const message = fieldTitle
        ? Drupal.t(
            '!field field is required.',
            { '!field': fieldTitle as string },
            { context: 'Grants application: Validation' },
          )
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
  formData: any,
): RJSFValidationError[] => {
  const expanded: RJSFValidationError[] = [];

  for (const error of errors) {
    expanded.push(error);

    if ((error as any).name !== 'required' || !error.property) {
      continue;
    }

    const parts = error.property.split('.').filter(Boolean);
    if (parts.length < 2) {
      continue;
    }

    // Check if the missing property is absent from form data
    const parentParts = parts.slice(0, -1);
    const missingFieldId = parts[parts.length - 1];
    const parentData = parentParts.reduce((data: any, key: string) => data?.[key], formData);
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
  if (!steps || !errors || !errors?.length) {
    return [];
  }

  const keyedErrors: Array<[number, RJSFValidationError]> = [];
  const stepsArray = Array.from(steps);

  errors.forEach((error) => {
    const match = error?.property?.match(regex)?.[0];

    const matchedStep = stepsArray.find(([index, step]) => step.id === match?.split('.')[1]);

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
export const isValidFormResponse = (data: Object): [boolean, string | undefined] => [true, undefined];

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
  grantsProfile: Array<undefined> | { business_id: string },
): [RJSFSchema, UiSchema] => {
  const { definitions, properties } = schema;
  const [rootProperty, definition, uiSchemaAdditions] = communitySettings;

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
    const hasProperty = current && Object.prototype.hasOwnProperty.call(current, propertyName);
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
export function* findFieldsOfType(element: any, type: string, prefix: string = ''): IterableIterator<string> {
  const isObject = typeof element === 'object' && !Array.isArray(element) && element !== null;

  if (isObject && element['ui:field'] && element['ui:field'] === type) {
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
export function* findFieldsWithOption(element: any, option: string, prefix: string = ''): IterableIterator<string> {
  const isObject = typeof element === 'object' && !Array.isArray(element) && element !== null;

  if (isObject && element[option]) {
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
export const getSubventionSum = (formData: any, subventionFields: string[]) =>
  subventionFields.reduce((total, field) => {
    const values = getNestedSchemaProperty(formData, field);

    if (values?.length) {
      Object.entries(values).forEach(([, curr]) => {
        const amount = Number(String(curr[1].value).replace(',', '.'));

        if (!Number.isNaN(amount)) {
          total += amount;
        }
      });
    }

    return total;
  }, 0);

/**
 * Check if the form is in draft mode.
 *
 * @return {boolean} - Whether form is a draft
 */
export const isDraft = () => drupalSettings.grants_react_form.use_draft;

const ALLOWED_TAGS = new Set(['p', 'ul', 'ol', 'li', 'strong']);

/**
 * Parse HTML content for textParagraphs and tooltips.
 *
 * @param {string} html - HTML content to parse
 * @return {React.ReactNode} - Parsed HTML content
 */
export const parseAllowedHtml = (html: string) =>
  parse(html, {
    replace(node) {
      const el = node as Element;
      if (el.type === 'tag' && !ALLOWED_TAGS.has(el.name)) {
        return <>{domToReact(el.children as DOMNode[])}</>;
      }
    },
  });

/**
 * Get the tooltip component.
 *
 * @param {object} uiSchema - uiSchema
 * @return {React.ReactNode} - Tooltip component
 */
export const getTooltip = (uiSchema: UiSchema | undefined) => {
  if (!uiSchema || !uiSchema?.['ui:options']?.tooltipText) {
    return undefined;
  }

  return (
    <Tooltip
      className='hdbt-react-form__tooltip-container'
      buttonLabel={uiSchema?.['ui:options']?.tooltipButtonLabel?.toString()}
      tooltipLabel={uiSchema?.['ui:options']?.tooltipLabel?.toString()}
    >
      {parseAllowedHtml(uiSchema['ui:options'].tooltipText.toString())}
    </Tooltip>
  );
};

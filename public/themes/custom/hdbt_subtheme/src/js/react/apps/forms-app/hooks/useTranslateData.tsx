// biome-ignore-all lint/suspicious/noExplicitAny: @todo UHF-12501
import type { RJSFSchema, UiSchema } from '@rjsf/utils';
import { useTranslation } from 'react-i18next';

/**
 * Check if value should be translated.
 *
 * @param {string} value - value to check
 * @return {boolean} - result
 */
const isTranslatableString = (value: string) => typeof value === 'string' && value.includes('.');

/**
 * Translates given data using the provided schema and uiSchema.
 * The translated data is then returned in an object with the same structure as the input data.
 * The schema and uiSchema are recursively translated, and the translated values are replaced in the same structure.
 *
 * @param {object} data - An object that contains the schema and uiSchema to be translated.
 * @return {object} An object with the same structure as the input data, but with the translated values.
 */
export const useTranslateData = (data: any) => {
  const { t } = useTranslation();
  const { schema, ui_schema } = data;

  const translateSchemaElement = (element: any): any => {
    const result: any = { ...element };
    ['addText', 'description', 'title', 'default'].forEach((key: string) => {
      if (element[key] && isTranslatableString(element[key])) {
        result[key] = t(element[key]);
      }
    });

    return result;
  };

  const iterateSchema = (element: any): any => {
    if (typeof element === 'string') {
      return t(element);
    }

    const translations: RJSFSchema = translateSchemaElement(element);
    const result: RJSFSchema = { ...element, ...translations };

    ['properties', 'definitions'].forEach((key: string) => {
      if (element?.[key]) {
        const keyResult: RJSFSchema = {};
        Object.entries(element[key]).forEach(([subKey, value]) => {
          keyResult[subKey] = iterateSchema(value);
        });
        result[key] = keyResult;
      }
    });

    ['if', 'then', 'additionalItems'].forEach((key: string) => {
      if (element?.[key]) {
        result[key] = iterateSchema(element[key]);
      }
    });

    if (element?.items && Array.isArray(element.items)) {
      result.items = element.items.map((item: RJSFSchema) => iterateSchema(item));
    }

    if (element?.enum && Array.isArray(element.enum)) {
      result.enum = element.enum.map((item: string) => (isTranslatableString(item) ? t(item) : item));
    }

    if (element?.options && Array.isArray(element.options)) {
      result.options = element.options.map(({ label, ...rest }) => ({
        ...rest,
        label: t(label),
      }));
    }

    if (element.allOf && Array.isArray(element.allOf)) {
      result.allOf = element.allOf.map((item: RJSFSchema) => iterateSchema(item));
    }

    return result;
  };

  const translateUiSchemaElement = (element: string, key: string) => {
    const translatableKeys = ['printableName', 'removeText', 'ui:help'];

    if (translatableKeys.includes(key)) {
      return t(element);
    }

    const tranlatableArrays = ['ui:enumNames'];

    if (tranlatableArrays.includes(key) && Array.isArray(element)) {
      return element.map((item: string) => (isTranslatableString(item) ? t(item) : item));
    }

    return element;
  };

  const iterateUiSchema = (uiSchema: UiSchema) => {
    const result: UiSchema = {};

    if (!uiSchema) {
      return result;
    }

    Object.entries(uiSchema).forEach(([key, value]) => {
      if (typeof value === 'object' && !Array.isArray(value) && value !== null) {
        result[key] = iterateUiSchema(value);
      } else {
        result[key] = translateUiSchemaElement(value, key);
      }
    });

    return result;
  };

  return {
    ...data,
    schema: iterateSchema(schema),
    ui_schema: iterateUiSchema(ui_schema),
  };
};

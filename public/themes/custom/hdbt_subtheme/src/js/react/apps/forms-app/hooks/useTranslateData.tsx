import { RJSFSchema, UiSchema } from '@rjsf/utils';
import { useTranslation } from 'react-i18next';

export const useTranslateData = (data: any) => {
  const { t } = useTranslation();
  const { schema, ui_schema } = data;

  const translateSchemaElement = (element: any): any => {
    const result: any = {};
    ['addText', 'description', 'title'].forEach((key: string) => {
      if (element[key]) {
        result[key] = t(element[key]);
      }
    })

    return result;
  };

  const iterateSchema = (element: any): any => {
    if (typeof element === 'string') {
      return t(element);
    }

    const translations: RJSFSchema = translateSchemaElement(element);
    const result: RJSFSchema = {...element, ...translations};
    
    ['properties', 'definitions'].forEach((key: string) => {
      if (element?.[key]) {
        const keyResult: RJSFSchema = {};
        Object.entries(element[key]).forEach(([subKey, value]) => {
          keyResult[subKey] = iterateSchema(value);
        });
        result[key] = keyResult;
      }
    });

    if (element.additionalItems) {
      result.additionalItems = iterateSchema(element.additionalItems);
    }

    if (element?.items) {
      result.items = element.items.map((item: RJSFSchema) => iterateSchema(item))
    }

    return result;
  };

  const translateUiSchemaElement = (element: string, key: string) => {
    const translatableKeys = [
      'removeText',
      'ui:help',
      'ui:tooltip',
    ]

    if (translatableKeys.includes(key)) {
      return t(element);
    }

    return element;
  };

  const iterateUiSchema = (uiSchema: UiSchema) => {
    const result: UiSchema = {};

    if (!uiSchema) {
      return result;
    }

    Object.entries(uiSchema).forEach(([key, value]) => {
      if (typeof value === 'object' && value !== null) {
        result[key] = iterateUiSchema(value);
      }
      else {
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

// biome-ignore-all lint/suspicious/noPrototypeBuiltins: @todo UHF-12501
// biome-ignore-all lint/suspicious/noExplicitAny: @todo UHF-12501
import type { RJSFSchema } from '@rjsf/utils';
import { useSetAtom, useStore } from 'jotai';
import { useAtomCallback } from 'jotai/utils';
import { useCallback } from 'react';
import { SubmitStates } from '../enum/SubmitStates';
import { useTranslateData } from '../hooks/useTranslateData';
import {
  avus2DataAtom,
  createFormDataAtom,
  formDataAtomRef,
  getApplicationNumberAtom,
  getSubmitStatusAtom,
  initializeFormAtom,
  pushNotificationAtom,
} from '../store';
import type { ATVFile } from '../types/ATVFile';
import {
  addApplicantInfoStep,
  getNestedSchemaProperty,
  setNestedProperty,
} from '../utils';
import { RJSFFormContainer } from './RJSFFormContainer';

/**
 * Get form paths for dangling arrays in dot notation.
 *
 * @param {any} element - current form element
 * @param {string} prefix - current form element path in dot notation
 *
 * @yields {string} - form element path
 */
function* iterateFormData(
  element: any,
  prefix: string = '',
): IterableIterator<string> {
  if (
    typeof element === 'object' &&
    !Array.isArray(element) &&
    element !== null
  ) {
    // Functional loops mess mess up generator function, so use for - of loop here.
    // eslint-disable-next-line no-restricted-syntax
    for (const [key, value] of Object.entries(element)) {
      yield* iterateFormData(value, `${prefix}.${key}`);
    }
  }

  // Element is an empty array when it should be object
  if (Array.isArray(element) && element.length === 0) {
    yield prefix;
  }

  // Element is array element with empty array as the only element
  if (
    Array.isArray(element) &&
    element.length === 1 &&
    Array.isArray(element[0]) &&
    element[0].length === 0
  ) {
    yield prefix;
  }
}

/**
 * Iterate over all attachments in a form data object.
 *
 * @param {any} element - Form data object
 *
 * @yields {ATVFile} - Attachment
 */
function* getAttachments(element: any): IterableIterator<ATVFile> {
  if (!element || typeof element !== 'object') {
    return;
  }

  if (element.hasOwnProperty('fileId')) {
    yield element;
  }

  // eslint-disable-next-line no-restricted-syntax
  for (const [, value] of Object.entries(element)) {
    yield* getAttachments(value);
  }
}

/**
 * Checks if a given schema definition should be fixed for an array field.
 * The definition is fixed if it is an array with an object type item,
 * or if it has a reference to another schema definition.
 *
 * @param {RJSFSchema} schemaDefinition - The schema definition to check.
 *
 * @return {boolean} - True if the schema definition should be fixed, false otherwise.
 */
const shouldFixArrayField = (schemaDefinition: RJSFSchema) => {
  if (
    schemaDefinition?.type !== 'array' ||
    !schemaDefinition?.items ||
    schemaDefinition?.items === true
  ) {
    return false;
  }

  const isObject =
    Array.isArray(schemaDefinition?.items) &&
    typeof schemaDefinition?.items[0] === 'object' &&
    schemaDefinition?.items[0]?.type === 'object';

  const hasRef =
    !Array.isArray(schemaDefinition.items) && schemaDefinition.items.$ref;

  return isObject || hasRef;
};

/**
 * Fix issue with backend returning arrays instead of empty objects.
 *
 * @todo see if this can be done in a less overengineered way
 *
 * @param {object} formData - Form data
 * @param {object} schema - Form schema
 *
 * @return {object} - Fixed form data
 */
const fixDanglingArrays = (formData: any, schema: RJSFSchema) => {
  const objectPaths = Array.from(iterateFormData(formData));

  objectPaths.forEach((path) => {
    const schemaDefinition =
      schema?.definitions && getNestedSchemaProperty(schema.definitions, path);

    if (schemaDefinition && schemaDefinition.type === 'object') {
      setNestedProperty(formData, path, {});
    }

    if (schemaDefinition && shouldFixArrayField(schemaDefinition)) {
      setNestedProperty(formData, path, [{}]);
    }
  });

  return formData;
};

/**
 * Transform data from source to include:
 * - Static applicant info form step as the first step
 * - Add _step property to each form step
 * - Add _isSection property to each form section
 *
 * @param {object} data - Raw data from server
 * @return {object} - Resulting data
 */
const transformData = (data: any) => {
  const {
    grants_profile,
    form_data: formData,
    schema: originalSchema,
    ui_schema: originalUiSchema,
  } = data;

  const [schema, ui_schema] = addApplicantInfoStep(
    originalSchema,
    originalUiSchema,
    grants_profile,
  );
  const { definitions, properties } = schema;
  const transformedProperties: any = {};

  // Add _step property to each form step
  if (properties) {
    Object.entries(properties).forEach((property: any) => {
      const [key, value] = property;
      transformedProperties[key] = { ...value, _step: key };
    });
  }

  // Add _isSection property to each form section
  if (definitions) {
    Object.entries(definitions).forEach((definition: any) => {
      const [key, definitionValue] = definition;

      if (key.charAt(0) !== '_') {
        Object.entries(definitionValue.properties).forEach(
          (subProperty: any) => {
            const [subKey] = subProperty;
            definitions[key].properties[subKey]._isSection = true;
          },
        );
      }
    });
  }

  return {
    ...data,
    formData: fixDanglingArrays(formData, schema),
    schema: { ...schema, properties: transformedProperties },
    ui_schema,
  };
};

/**
 * Wrapper for RJSF form.
 *
 * @typedef {object} FormWrapperProps
 * @prop {string} applicationTypeId
 *
 * @param {FormWrapperProps} props - JSX props
 * @return {JSX.Element} - RJSF form
 */
export const FormWrapper = ({
  applicationTypeId,
  data,
  token,
}: {
  applicationTypeId: string;
  data: any;
  token: string;
}) => {
  const store = useStore();
  const initializeForm = useSetAtom(initializeFormAtom);
  const pushNotification = useSetAtom(pushNotificationAtom);
  const readApplicationNumber = useAtomCallback(
    useCallback((get) => get(getApplicationNumberAtom), []),
  );
  const readSubmitStatus = useAtomCallback(
    useCallback((get) => get(getSubmitStatusAtom), []),
  );
  const transformedData = transformData(data);
  const translatedData = useTranslateData(transformedData);
  const setAvus2Data = useSetAtom(avus2DataAtom);

  initializeForm(translatedData);

  const { currentLanguage } = drupalSettings.path;

  const handleResponseError = async (
    response: Response,
    actionType: 'submit' | 'draft',
  ): Promise<void> => {
    const json = await response.json();
    const { error } = json;

    if (!error) {
      throw new Error('Unexpected backend error while submitting.');
    }

    const label =
      actionType === 'submit'
        ? Drupal.t(
            'Application could not be submitted.',
            {},
            { context: 'Grants application: Submit' },
          )
        : Drupal.t(
            'Application could not be saved as draft.',
            {},
            { context: 'Grants application: Draft' },
          );

    pushNotification({ children: <div>{error}</div>, label, type: 'error' });
  };
  const submitData = async (submittedData: any): Promise<void> => {
    const response = await fetch(
      `/${currentLanguage}/applications/${applicationTypeId}/application/${readApplicationNumber()}`,
      {
        body: JSON.stringify({
          application_number: readApplicationNumber() || '',
          application_type_id: applicationTypeId,
          attachments: Array.from(getAttachments(submittedData)),
          form_data: submittedData,
          langcode: 'en',
        }),
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
        method: readSubmitStatus() === SubmitStates.DRAFT ? 'POST' : 'PATCH',
      },
    );

    if (!response.ok) {
      await handleResponseError(response, 'submit');
      return;
    }

    const json = await response.json();
    const { redirect_url } = json;

    window.location.href = redirect_url;
  };

  const formDataAtom = createFormDataAtom(
    translatedData.applicationNumber,
    translatedData.formData,
    data?.last_changed,
  );
  store.set(formDataAtomRef, formDataAtom);

  if (translatedData.status !== SubmitStates.DRAFT) {
    const { attachmentsInfo, statusUpdates, events, messages } =
      translatedData.form_data;

    setAvus2Data({ attachmentsInfo, statusUpdates, events, messages });
  }

  const saveDraft = async (submittedData: any) => {
    const response = await fetch(
      `/${currentLanguage}/applications/${applicationTypeId}/${readApplicationNumber()}`,
      {
        body: JSON.stringify({
          application_number: readApplicationNumber() || '',
          application_type_id: applicationTypeId,
          attachments: Array.from(getAttachments(submittedData)),
          form_data: submittedData,
          langcode: 'en',
        }),
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
        method: 'PATCH',
      },
    );

    if (!response.ok) {
      await handleResponseError(response, 'draft');
      return;
    }

    const redirectUrl = drupalSettings.grants_react_form.list_view_path;
    window.location.href = redirectUrl;
  };

  return (
    <RJSFFormContainer
      formDataAtom={formDataAtom}
      saveDraft={saveDraft}
      schema={translatedData.schema}
      submitData={submitData}
      uiSchema={translatedData.ui_schema}
    />
  );
};

export default FormWrapper;

// @ts-nocheck
import useSWRImmutable from 'swr/immutable'
import { useSetAtom } from 'jotai';
import { LoadingSpinner } from 'hds-react';
import { useAtomCallback } from 'jotai/utils';
import { Suspense, useCallback } from 'react';
import { RJSFSchema } from '@rjsf/utils';

import { RJSFFormContainer } from './RJSFFormContainer';
import { createFormDataAtom, getApplicationNumberAtom, initializeFormAtom, pushNotificationAtom, setSubmitStatusAtom } from '../store';
import { addApplicantInfoStep, getNestedSchemaProperty, isValidFormResponse, setNestedProperty } from '../utils';
import { SubmitStates } from '../enum/SubmitStates';

const instantiateDocument = async(id: string, token: string) => {
  const response = await fetch(`/applications/${id}`, {
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
    method: 'POST',
  });

  if (!response.ok) {
    throw new Error('Failed to instantiate application');
  }

  return response.json();
};

/**
 * Fetcher function for the form data.
 * Queries form data from the server.
 * Checks IndexedDB for existing form data.
 *
 * @param {string} id - The form id
 * @param {string} token - CSRF token
 * @return {Promise<object>} - Form settings and existing cached form data
 */
async function fetchFormData(id: string, token: string) {
  const params = new URLSearchParams(window.location.search);
  let applicationNumber = params.get('application_number');

  if (!applicationNumber) {
    const { application_number } = await instantiateDocument(id, token);
    applicationNumber = application_number;
  }

  const formConfigResponse = await fetch(`/applications/${id}/${applicationNumber}`, {
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': token,
    },
  });

  if (!formConfigResponse.ok) {
    throw new Error('Failed to fetch form data');
  }

  const formConfig = await formConfigResponse.json();
  const persistedData = { ...formConfig.form_data};

  return {
    ...formConfig,
    persistedData,
    applicationNumber,
  };
};

/**
 * Get form paths for dangling arrays in dot notation.
 *
 * @param {any} element - current form element
 * @param {string} prefix - current form element path in dot notation
 *
 * @yields {string} - form element path
 */
function* iterateFormData(element: any, prefix: string = '') {

  if (typeof element === 'object' && !Array.isArray(element) && element !== null) {
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
};

function* getAttachments(element: any) {
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
 * Fix issue with backend returning arrays instead of empty objects.
 *
 * @todo see if this can be done in a less overengineered way
 *
 * @param {object} formData - Form data
 * @param {object} schema - Form schema
 *
 * @return {object} - Fixed form data
 */
const fixDanglingArrays = (formData: Object<any>, schema: RJSFSchema) => {

  const objectPaths = Array.from(iterateFormData(formData));

  objectPaths.forEach(path => {
    const schemaDefinition = getNestedSchemaProperty(schema.definitions, path);

    if (schemaDefinition && schemaDefinition.type === 'object') {
      setNestedProperty(formData, path, {});
    }

    if (schemaDefinition.type === 'array' && schemaDefinition.items[0].type === 'object') {
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

  const [schema, ui_schema] = addApplicantInfoStep(originalSchema, originalUiSchema, grants_profile)
  const {
    definitions,
    properties,
  } = schema;
  const transformedProperties: any = {};

  // Add _step property to each form step
  Object.entries(properties).forEach((property: any) => {
    const [key, value] = property;
    transformedProperties[key] = {
      ...value,
      _step: key,
    };
  });

  // Add _isSection property to each form section
  Object.entries(definitions).forEach((definition: any) => {
    const [key, definitionValue] = definition;

    Object.entries(definitionValue.properties).forEach((subProperty: any) => {
      const [subKey] = subProperty;
      definitions[key].properties[subKey]._isSection = true;
    });
  });

  return {
    ...data,
    formData: fixDanglingArrays(formData, schema),
    schema: {
      ...schema,
      properties: transformedProperties,
    },
    ui_schema,
  };
};

type FormWrapperProps = {
  applicationTypeId: string;
  token: string;
};

/**
 * The root container for the app.
 * Queries form settings and data based on the application ID.
 * Renders RJSF form.
 *
 * @typedef {object} FormWrapperProps
 * @prop {string} applicationTypeId
 *
 * @param {FormWrapperProps} props - JSX props
 * @return {JSX.Element} - RJSF form
 */
const FormWrapper = ({
  applicationTypeId,
  token,
}: FormWrapperProps) => {
  const { data, isLoading, isValidating, error } = useSWRImmutable(
    applicationTypeId,
    (id) => fetchFormData(id, token),
  );

  const initializeForm = useSetAtom(initializeFormAtom);
  const setSubmitStatus = useSetAtom(setSubmitStatusAtom);
  const pushNotification = useSetAtom(pushNotificationAtom);
  const readApplicationNumber = useAtomCallback(
    useCallback(get => get(getApplicationNumberAtom), [])
  );

  if (isLoading || isValidating) {
    return  <LoadingSpinner />
  }
  const [responseValid, errorMessage] = isValidFormResponse(data);
  if (!responseValid || error) {
    throw new Error(errorMessage);
  }

  const transformedData = transformData(data);
  initializeForm(transformedData);


  const submitData = async (submittedData: any): Promise<boolean> => {
    const response = await fetch(`/en/applications/${applicationTypeId}/application/${readApplicationNumber()}`, {
      body: JSON.stringify({
        application_number: readApplicationNumber() || '',
        application_type_id: applicationTypeId,
        attachments: Array.from(getAttachments(submittedData)),
        form_data: submittedData,
        langcode: 'en',
      }),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      method: 'POST',
    });

    if (!response.ok) {
      return false;
    }

    // @todo read submit status from server response
    setSubmitStatus(SubmitStates.submitted);

    return response.ok;
  };

  const initialData = transformedData.form_data?.form_data || null;
  const formDataAtom = createFormDataAtom(transformedData.applicationNumber, initialData);

  const saveDraft = async (submittedData: any) => {
    const response = await fetch(`/applications/${applicationTypeId}/${readApplicationNumber()}`, {
      body: JSON.stringify({
        application_number: readApplicationNumber() || '',
        application_type_id: applicationTypeId,
        attachments: Array.from(getAttachments(submittedData)),
        form_data: submittedData,
        langcode: 'en',
      }),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token,
      },
      method: 'PATCH',
    });

    if (response.ok) {
      pushNotification({
        children: <div>{Drupal.t('Application saved as draft.')}</div>,
        label: Drupal.t('Save successful.'),
        type: 'success',
      });
    }
    else {
      pushNotification({
        children: <div>{Drupal.t('Application could not be saved as draft.')}</div>,
        label: Drupal.t('Save failed.'),
        type: 'error',
      });
    }

    return response.ok;
  };

  return (
    <Suspense fallback={<LoadingSpinner />}>
      <RJSFFormContainer
        formDataAtom={formDataAtom}
        saveDraft={saveDraft}
        schema={transformedData.schema}
        submitData={submitData}
        uiSchema={transformedData.ui_schema}
      />
    </Suspense>
  );
};

export default FormWrapper;

// @ts-nocheck
import useSWRImmutable from 'swr/immutable'
import { useSetAtom } from 'jotai';
import { LoadingSpinner } from 'hds-react';
import { Suspense } from 'react';
import { RJSFSchema } from '@rjsf/utils';
import { RJSFFormContainer } from './RJSFFormContainer';
import { initializeFormAtom, setApplicationNumberAtom, setSubmitStatusAtom } from '../store';
import { addApplicantInfoStep, getNestedProperty, isValidFormResponse, setNestedProperty } from '../utils';
import { getData, initDB } from '../db';
import { SubmitStates } from '../enum/SubmitStates';

/**
 * Fetcher function for the form data.
 * Queries form data from the server.
 * Checks IndexedDB for existing form data.
 *
 * @param {string} id - The form id
 * @return {Promise<object>} - Form settings and existing cached form data
 */
async function fetchFormData(id: string) {
  const params = new URLSearchParams(window.location.search);
  const applicationNumber = params.get('application_number');

  await initDB();
  const formConfigResponse = await fetch(`/application/${id}${applicationNumber ? `/${applicationNumber}` : ''}`, {
    headers: {
      'Content-Type': 'application/json',
    }
  });
  const cachedData = await getData(applicationNumber || '58');

  if (!formConfigResponse.ok) {
    throw new Error('Failed to fetch form data');
  }

  const formConfig = await formConfigResponse.json();

  // @todo decide when we want to use cached data over server data
  const persistedData = (formConfig.form_data && applicationNumber) ? formConfig.form_data : cachedData;

  return {
    ...formConfig,
    persistedData,
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
    const schemaDefinition = getNestedProperty(schema.definitions, path);

    if (schemaDefinition && schemaDefinition.type === 'object') {
      setNestedProperty(formData, path);
    }

    if (schemaDefinition.type === 'array') {
      setNestedProperty(formData, path, []);
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
    form_data: originalData,
    grants_profile,
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
    form_data: fixDanglingArrays(originalData, schema),
    schema: {
      ...schema,
      properties: transformedProperties,
    },
    ui_schema,
  };
};

type FormWrapperProps = {
    applicationNumber: string
};

/**
 * The root container for the app.
 * Queries form settings and data based on the application ID.
 * Renders RJSF form.
 *
 * @typedef {object} FormWrapperProps
 * @prop {string} applicationNumber
 *
 * @param {FormWrapperProps} props - JSX props
 * @return {JSX.Element} - RJSF form
 */
const FormWrapper = ({
  applicationNumber,
}: FormWrapperProps) => {
  const { data, isLoading, isValidating, error } = useSWRImmutable(applicationNumber, fetchFormData);
  const initializeForm = useSetAtom(initializeFormAtom);
  const setSubmitStatus = useSetAtom(setSubmitStatusAtom);
  const setApplicationNumber = useSetAtom(setApplicationNumberAtom);

  if (isLoading || isValidating) {
    return  <LoadingSpinner />
  }
  const [responseValid, errorMessage] = isValidFormResponse(data);
  if (!responseValid || error) {
    throw new Error(errorMessage);
  }

  const transformedData = transformData(data);
  initializeForm({
    ...transformedData
  });

  const submitData = async (submittedData: any): Promise<boolean> => {
    const response = await fetch(`/en/application/${applicationNumber}`, {
      body: JSON.stringify({
        application_number: '',
        application_type_id: applicationNumber,
        form_data: submittedData,
        langcode: 'en',
      }),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': data.token
      },
      method: 'POST',
    });

    if (response.ok) {
      // @todo read submit state from response
      setSubmitStatus(SubmitStates.submitted);
      const json = await response.json();
      const { metadata } = json;

      setApplicationNumber(metadata.applicationnumber);

      return true;
    }

    return false;
  };

  return (
    <Suspense fallback={<LoadingSpinner />}>
      <RJSFFormContainer
        initialFormData={transformedData.persistedData}
        schema={transformedData.schema}
        submitData={submitData}
        uiSchema={transformedData.ui_schema}
      />
    </Suspense>
  );
};

export default FormWrapper;

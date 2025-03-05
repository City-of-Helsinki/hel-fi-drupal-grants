// @ts-nocheck
import useSWRImmutable from 'swr/immutable'
import { useSetAtom } from 'jotai';
import { IChangeEvent } from '@rjsf/core';
import { RJSFFormContainer } from './RJSFFormContainer';
import { initializeFormAtom } from '../store';
import { LoadingSpinner } from 'hds-react';
import { addApplicantInfoStep, isValidFormResponse } from '../utils';
import { Suspense, useState } from 'react';
import { getData, initDB } from '../db';

/**
 * Fetcher function for the form data.
 * Queries form data from the server.
 * Checks IndexedDB for existing form data.
 *
 * @param {string} id - The form id
 * @returns {Promise<object>} - Form settings and existing cached form data
 */
async function fetchFormData(id: string) {
  const dbInitialized = await initDB();
  const formConfigResponse = await fetch(`/en/application/${id}`);
  const persistedData = await getData();

  if (!formConfigResponse.ok) {
    throw new Error('Failed to fetch form data');
  }

  const formConfig = await formConfigResponse.json();

  return {
    ...formConfig,
    persistedData,
  };
};

/**
 * Transform data from source to include:
 * - Static applicant info form step as the first step
 * - Add _step property to each form step
 * - Add _isSection property to each form section
 *
 * @param {object} data - Raw data from server
 * @returns {object} - Resulting data
 */
const transformData = (data: any) => {
  const {
    schema: originalSchema,
    grants_profile,
    ui_schema: originalUiSchema,
  } = data;

  const [schema, ui_schema] = addApplicantInfoStep(originalSchema, originalUiSchema, grants_profile)
  const {
    definitions,
    properties,
  } = schema;
  const transformedProperties: any = {};

  Object.entries(properties).forEach((property: any, index: number) => {
    const [key, value] = property;
    transformedProperties[key] = {
      ...value,
      _step: key,
    };
  });

  Object.entries(definitions).forEach((definition: any) => {
    const [key, definitionValue] = definition;

    Object.entries(definitionValue.properties).forEach((subProperty: any) => {
      const [subKey] = subProperty;
      definitions[key].properties[subKey]._isSection = true;
    });
  });

  return {
    ...data,
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
 * @property {string} applicationNumber
 *
 * @param {FormWrapperProps} props
 * @returns {JSX.Element}
 */
const FormWrapper = ({
  applicationNumber,
}: FormWrapperProps) => {
  const { data, isLoading, isValidating, error } = useSWRImmutable(applicationNumber, fetchFormData);
  const initializeForm = useSetAtom(initializeFormAtom);

  if (isLoading || isValidating) {
    return  <LoadingSpinner />
  }
  const [responseValid, errorMessage] = isValidFormResponse(data);
  if (!responseValid) {
    throw new Error(errorMessage);
  }

  const transformedData = transformData(data);
  initializeForm({
    ...transformedData
  });

  const submitData = async (formSubmitEvent: IChangeEvent) => {
    await fetch(`/en/application/${applicationNumber}`, {
      method: 'POST',
      body: JSON.stringify(formSubmitEvent.formData),
    });
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

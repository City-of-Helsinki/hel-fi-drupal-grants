// @ts-nocheck
import useSWRImmutable from 'swr/immutable'
import { useSetAtom } from 'jotai';
import { IChangeEvent } from '@rjsf/core';
import { RJSFFormContainer } from './RJSFFormContainer';
import { initializeFormAtom } from '../store';
import { LoadingSpinner } from 'hds-react';
import { addApplicantInfoStep, isValidFormResponse } from '../utils';

const fetchFormData = async(id: string) => {
  const response = await fetch(`/en/application/${id}`);

  if (!response.ok) {
    throw new Error('Failed to fetch form data');
  }

  return response.json();
};

const transformSchema = (data: any) => {
  const {
    schema: originalSchema,
    grants_profile,
  } = data;

  const schema = addApplicantInfoStep(originalSchema, grants_profile)
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
    ...schema,
    properties: transformedProperties,
  };
};

type FormWrapperProps = {
    applicationNumber: string
};

const FormWrapper = ({
  applicationNumber,
}: FormWrapperProps) => {
  const { data, isLoading, isValidating, } = useSWRImmutable(applicationNumber, fetchFormData);
  const initializeForm = useSetAtom(initializeFormAtom);

  if (isLoading || isValidating) {
    return  <LoadingSpinner />
  }

  const [responseValid, errorMessage] = isValidFormResponse(data);
  if (!responseValid) {
    throw new Error(errorMessage);
  }

  const transformedSchema = transformSchema(data);
  initializeForm({
    ...data,
    schema: transformedSchema,
  });

  const submitData = async (formSubmitEvent: IChangeEvent) => {
    await fetch(`/en/application/${applicationNumber}`, {
      method: 'POST',
      body: JSON.stringify(formSubmitEvent.formData),
    });
  };

  return (
    <RJSFFormContainer
      schema={transformedSchema}
      submitData={submitData}
      uiSchema={data.ui_schema}
    />
  );
};

export default FormWrapper;

import useSWRImmutable from 'swr/immutable'
import { useSetAtom } from 'jotai';
import { IChangeEvent } from '@rjsf/core';
import { RJSFFormContainer } from './RJSFFormContainer';
import { initializeFormAtom } from '../store';

const fetchFormData = async(id: string) => {
  const response = await fetch(`/en/application/${id}`);

  if (!response.ok) {
    throw new Error('Failed to fetch form data');
  }

  return response.json();
};

const transformSchema = (data: any) => {
  const {
    schema,
    schema: {
      definitions,
      properties,
    },
  } = data;

  const transformedProperties: any = {};

  Object.entries(properties).forEach((property: any) => {
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
    return <div>loading...</div>
  }

  if (!data.schema || !data.ui_schema) {
    return <div>wrong response...</div>
  }

  initializeForm(data);
  const transformedSchema = transformSchema(data);

  const submitData = async (formSubmitEvent: IChangeEvent) => {
    await fetch(`/en/application/${applicationNumber}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': data.token
      },
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

import useSWRImmutable from 'swr/immutable'
import { useSetAtom } from 'jotai';
import { ADDITIONAL_PROPERTIES_KEY } from '@rjsf/utils';
import { RJSFFormContainer } from './RJSFFormContainer';
import { initializeFormAtom } from '../store';
import { Stepper } from '../components/Stepper';
import { IChangeEvent } from '@rjsf/core';

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

  Object.entries(properties).forEach((property: any, index: number) => {
    const [key, value] = property;
    transformedProperties[key] = {
      ...value,
      _step: key,
    };
  });

  for(const definition in definitions) {
    for(const subProperty in definitions[definition].properties) {
      definitions[definition].properties[subProperty]._isSection = true;
    }
  }

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

  const submitData = async (data: IChangeEvent) => {
    const submitResult = await fetch(`/en/application/${applicationNumber}`, {
      method: 'POST',
      body: JSON.stringify(data.formData),
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

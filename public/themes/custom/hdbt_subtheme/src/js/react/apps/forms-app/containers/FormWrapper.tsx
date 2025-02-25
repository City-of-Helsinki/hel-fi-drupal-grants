import useSWRImmutable from 'swr/immutable'
import RJSFFormContainer from './RSJFFormContainer';
import { useSetAtom } from 'jotai';
import { initializeFormAtom } from '../store';
import { ADDITIONAL_PROPERTIES_KEY } from '@rjsf/utils';
import { Stepper } from '../components/Stepper';

const fetchFormData = async(id: string) => {
  const response = await fetch(`/en/application/${id}/preview`);

  if (!response.ok) {
    throw new Error('Failed to fetch form data');
  }

  return response.json();
};

const transformSchema = (data: any) => {
  const {
    schema,
    schema: {
      properties,
    },
    translations,
  } = data;

  let transformedProperties: any = {};

  Object.entries(properties).forEach((property: any, index: number) => {
    const [key, value] = property;

    const additionalProperties = value?.additionalProperties || {};

    transformedProperties[key] = {
      ...value,
      [ADDITIONAL_PROPERTIES_KEY]: {
        ...additionalProperties,
        step: key,
      },
    };
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

  return (
    <>
      <Stepper />
      <RJSFFormContainer
        schema={transformedSchema}
        uiSchema={data.ui_schema}
      />
    </>
  );
};

export default FormWrapper;

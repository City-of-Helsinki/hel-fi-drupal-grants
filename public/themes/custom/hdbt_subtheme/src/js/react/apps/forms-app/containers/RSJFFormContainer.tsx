import Form from '@rjsf/core';
import { RJSFSchema, RegistryFieldsType, RegistryWidgetsType, UiSchema } from '@rjsf/utils';
import validator from '@rjsf/validator-ajv8';
import React, { createRef, useEffect } from 'react';
import { TextArea, TextInput, SubmitButton, SelectWidget } from '../components/Input';
import { ArrayFieldTemplate, FieldsetWidget, ObjectFieldTemplate } from '../components/Templates';
import { useAtom, useAtomValue, useSetAtom } from 'jotai';
import { formConfigAtom, formStateAtom, getCurrentStepAtom } from '../store';
import { StaticStepsContainer } from './StaticStepsContainer';

const fetchFormData = async(id: string) => {
  const response = await fetch(`/en/application/${id}/preview`);

  if (!response.ok) {
    throw new Error('Failed to fetch form data');
  }

  return response.json();
};

const widgets: RegistryWidgetsType = {
  EmailWidget: TextInput,
  FieldsetWidget,
  SelectWidget,
  TextareaWidget: TextArea,
  TextWidget: TextInput,
};

type RJSFFormContainerProps = {
  schema: RJSFSchema,
  uiSchema: UiSchema,
};

const RJSFFormContainer = ({
  schema,
  uiSchema,
}: RJSFFormContainerProps) => {
  const formRef = createRef<Form>();

  return (
    <div className='form-wrapper'>
      <StaticStepsContainer formRef={formRef} />
      <Form
        className='grants-react-form webform-submission-form'
        method='POST'
        noHtml5Validate
        onError={errors => console.log(errors)}
        onSubmit={(data, event: React.FormEvent<HTMLFormElement>) => {
          event.preventDefault();

          try {
            formRef.current?.validateForm();
          }
          catch (e) {
            return;
          }

          console.log(data);
        }}
        ref={formRef}
        schema={schema}
        showErrorList={false}
        templates={{
          ArrayFieldTemplate,
          ButtonTemplates: { SubmitButton },
          FieldErrorTemplate: () => null,
          ObjectFieldTemplate,
        }}
        uiSchema={{
          ...uiSchema,
          'ui:globalOptions': {
            label: false,
          },
        }}
        validator={validator}
        widgets={widgets}
      />
    </div>
  );
}

export default RJSFFormContainer;

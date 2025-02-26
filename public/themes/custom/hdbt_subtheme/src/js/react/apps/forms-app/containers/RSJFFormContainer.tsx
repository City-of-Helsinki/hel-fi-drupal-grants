import Form from '@rjsf/core';
import { RJSFSchema, RegistryWidgetsType, UiSchema } from '@rjsf/utils';
import validator from '@rjsf/validator-ajv8';
import React, { createRef } from 'react';
import { TextArea, TextInput, SubmitButton, SelectWidget } from '../components/Input';
import { ArrayFieldTemplate, FieldsetWidget, ObjectFieldTemplate } from '../components/Templates';
import { StaticStepsContainer } from './StaticStepsContainer';

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
        onError={errors => null}
        onSubmit={(data, event: React.FormEvent<HTMLFormElement>) => {
          event.preventDefault();

          try {
            formRef.current?.validateForm();
          }
          catch (e) {
            console.error(e);
            
          }
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

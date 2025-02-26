import Form, { IChangeEvent } from '@rjsf/core';
import { RJSFSchema, RJSFValidationError, RegistryWidgetsType, UiSchema } from '@rjsf/utils';
import validator from '@rjsf/validator-ajv8';
import React, { createRef } from 'react';
import { TextArea, TextInput, SelectWidget } from '../components/Input';
import { ArrayFieldTemplate, FieldsetWidget, ObjectFieldTemplate } from '../components/Templates';
import { StaticStepsContainer } from './StaticStepsContainer';
import { FormActions } from '../components/FormActions';
import { Stepper } from '../components/Stepper';

const widgets: RegistryWidgetsType = {
  EmailWidget: TextInput,
  FieldsetWidget,
  SelectWidget,
  TextareaWidget: TextArea,
  TextWidget: TextInput,
};

type RJSFFormContainerProps = {
  schema: RJSFSchema,
  submitData: (data: IChangeEvent) => void,
  uiSchema: UiSchema,
};

export const RJSFFormContainer = ({
  submitData,
  schema,
  uiSchema,
}: RJSFFormContainerProps) => {
  const formRef = createRef<Form>();

  const onError = (errors: RJSFValidationError[]) => {
    if (errors.length) {
      formRef.current?.focusOnError(errors[0]);
    }
  };

  return (
    <>
      <Stepper formRef={formRef} />
      <div className='form-wrapper'>
        <StaticStepsContainer formRef={formRef} />
        <Form
          className='grants-react-form webform-submission-form'
          method='POST'
          noHtml5Validate
          onError={onError}
          onSubmit={(data, event: React.FormEvent<HTMLFormElement>) => {
            event.preventDefault();

            const passes = formRef.current?.validateForm();

            if (passes) {
              submitData(data);
            }
          }}
          ref={formRef}
          schema={schema}
          showErrorList={false}
          templates={{
            ArrayFieldTemplate,
            ButtonTemplates: { SubmitButton: () => null },
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
        >
          <FormActions formRef={formRef} />
        </Form>
      </div>
    </>
  );
}

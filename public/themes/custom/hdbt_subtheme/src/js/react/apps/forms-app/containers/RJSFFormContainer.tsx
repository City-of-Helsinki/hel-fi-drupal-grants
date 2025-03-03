import Form, { IChangeEvent } from '@rjsf/core';
import { ErrorTransformer, RJSFSchema, RJSFValidationError, RegistryWidgetsType, UiSchema } from '@rjsf/utils';
import validator from '@rjsf/validator-ajv8';
import React, { createRef, useCallback } from 'react';
import { useAtomValue, useSetAtom } from 'jotai';
import { useAtomCallback } from 'jotai/utils';
import { TextArea, TextInput, SelectWidget } from '../components/Input';
import { ObjectFieldTemplate } from '../components/Templates';
import { StaticStepsContainer } from './StaticStepsContainer';
import { FormActions } from '../components/FormActions';
import { Stepper } from '../components/Stepper';
import { getCurrentStepAtom, getReachedStepAtom, getStepsAtom, setErrorsAtom } from '../store';
import { keyErrorsByStep } from '../utils';
import { ErrorsList } from '../components/ErrorsList';

const widgets: RegistryWidgetsType = {
  EmailWidget: TextInput,
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
  const steps = useAtomValue(getStepsAtom);
  const formRef = createRef<Form>();
  const readCurrentStep = useAtomCallback(
    useCallback(get =>  get(getCurrentStepAtom), [])
  );
  const readReachedStep = useAtomCallback(
    useCallback(get => get(getReachedStepAtom), [])
  );
  const setErrors = useSetAtom(setErrorsAtom);

  const onError = (errors: RJSFValidationError[]) => {
    const keyedErrors = keyErrorsByStep(errors, steps);
    const [currentStepIndex] = readCurrentStep();

    const currentPageErrors = keyedErrors.filter(([index, error]) => index === currentStepIndex);

    if (currentPageErrors.length) {
      formRef.current?.focusOnError(currentPageErrors[0][1]);
    }
  };

  const validatePartialForm = () => {
    const data = formRef.current?.state.formData;
    formRef.current?.validateForm();

    return formRef.current?.validate(data);
  };

  const transformErrors: ErrorTransformer = (errors) => {
    const reachedStep = readReachedStep();
    const keyedErrors = keyErrorsByStep(errors, steps);

    const errorsToShow = keyedErrors.filter(([index]) => index <= reachedStep).map(([index, error]) => error);
    setErrors(errorsToShow);

    return errorsToShow;
  };

  return (
    <>
      <ErrorsList />
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
            ButtonTemplates: { SubmitButton: () => null },
            FieldErrorTemplate: () => null,
            ObjectFieldTemplate,
          }}
          transformErrors={transformErrors}
          uiSchema={{
            ...uiSchema,
            'ui:globalOptions': {
              label: false,
            },
          }}
          validator={validator}
          widgets={widgets}
        >
          <FormActions {...{validatePartialForm}} />
        </Form>
      </div>
    </>
  );
}

import Form, { IChangeEvent } from '@rjsf/core';
import { ErrorTransformer, RJSFSchema, RJSFValidationError, RegistryWidgetsType, UiSchema } from '@rjsf/utils';
import validator from '@rjsf/validator-ajv8';
import React, { createRef, useCallback } from 'react';
import { useAtomValue, useSetAtom } from 'jotai';
import { useAtomCallback } from 'jotai/utils';
import { useDebounceCallback } from 'usehooks-ts';

import { FileInput } from '../components/FileInput';
import { TextArea, TextInput, SelectWidget, AddressSelect, BankAccountSelect, CommunityOfficialsSelect } from '../components/Input';
import { AddButtonTemplate, ArrayFieldTemplate, ObjectFieldTemplate, RemoveButtonTemplate } from '../components/Templates';
import { StaticStepsContainer } from './StaticStepsContainer';
import { FormActions } from '../components/FormActions';
import { Stepper } from '../components/Stepper';
import { getCurrentStepAtom, getReachedStepAtom, getStepsAtom, setErrorsAtom } from '../store';
import { keyErrorsByStep } from '../utils';
import { ErrorsList } from '../components/ErrorsList';
import { addData } from '../db';

const widgets: RegistryWidgetsType = {
  'address': AddressSelect,
  'bank_account': BankAccountSelect,
  'community_officials': CommunityOfficialsSelect,
  EmailWidget: TextInput,
  SelectWidget,
  TextareaWidget: TextArea,
  TextWidget: TextInput,
  FileWidget: FileInput,
};

type RJSFFormContainerProps = {
  initialFormData: any,
  schema: RJSFSchema,
  submitData: (data: IChangeEvent) => void,
  uiSchema: UiSchema,
};

/**
 * Container for the RJSF form.
 *
 * @typedef {object} RJSFFormContainerProps
 * @prop {object} initialFormData - The initial data for the form.
 * @prop {object} schema - The schema for the form.
 * @prop {function} submitData - The function to call when the form is submitted.
 * @prop {object} uiSchema - The uiSchema for the form.
 *
 * @param {RJSFFormContainerProps} props - JSX props
 * @return {JSX.Element} - Element that renders
 */
export const RJSFFormContainer = ({
  initialFormData,
  schema,
  submitData,
  uiSchema,
}: RJSFFormContainerProps) => {
  const persistFormState = useDebounceCallback(
    (data: IChangeEvent) => {
      addData(data.formData);
    },
    2000,
  );
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
          formData={initialFormData}
          method='POST'
          noHtml5Validate
          onChange={persistFormState}
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
            ButtonTemplates: {
              AddButton: AddButtonTemplate,
              RemoveButton: RemoveButtonTemplate,
              SubmitButton: () => null
            },
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

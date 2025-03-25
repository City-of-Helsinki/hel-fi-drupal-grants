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
import { FormActions } from '../components/FormActions/FormActions';
import { Stepper } from '../components/Stepper';
import { getApplicationNumberAtom, getCurrentStepAtom, getReachedStepAtom, getStepsAtom, getSubmitStatusAtom, setErrorsAtom } from '../store';
import { keyErrorsByStep } from '../utils';
import { ErrorsList } from '../components/ErrorsList';
import { addData } from '../db';
import { SubmitStates } from '../enum/SubmitStates';

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
  submitData: (data: IChangeEvent, finalSubmit?: boolean) => boolean,
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
  const submitStatus = useAtomValue(getSubmitStatusAtom);
  const steps = useAtomValue(getStepsAtom);
  const formRef = createRef<Form>();
  const readApplicationNumber = useAtomCallback(
    useCallback(get => get(getApplicationNumberAtom), [])
  );
  const readCurrentStep = useAtomCallback(
    useCallback(get =>  get(getCurrentStepAtom), [])
  );
  const readReachedStep = useAtomCallback(
    useCallback(get => get(getReachedStepAtom), [])
  );
  const setErrors = useSetAtom(setErrorsAtom);

  const browserCacheData = useDebounceCallback(
    (data: IChangeEvent) => {
      addData(data.formData, readApplicationNumber() || '58');
    },
    2000,
  );

  /**
   * React to errors in the form.
   *
   * @param {array} errors - RJSF validation errors
   * @return {void}
   */
  const onError = (errors: RJSFValidationError[]) => {
    const keyedErrors = keyErrorsByStep(errors, steps);
    const [currentStepIndex] = readCurrentStep();

    const currentPageErrors = keyedErrors.filter(([index, error]) => index === currentStepIndex);

    if (currentPageErrors.length) {
      formRef.current?.focusOnError(currentPageErrors[0][1]);
    }
  };

  /**
   * Function that can be called to imperatively validate the form.
   *
   * @return {object} - validation result
   */
  const validatePartialForm = () => {
    const data = formRef.current?.state.formData;
    formRef.current?.validateForm();

    return formRef.current?.validate(data);
  };

  /**
   * Transforms RJSF-generated errors.
   *
   * @param {array} errors - RJSF validation errors
   * @return {array} - modified and filtered errors
   */
  const transformErrors: ErrorTransformer = (errors) => {
    const reachedStep = readReachedStep();
    const keyedErrors = keyErrorsByStep(errors, steps);

    const errorsToShow = keyedErrors
      .filter(([index]) => index <= reachedStep).map(([index, error]) => error);
    setErrors(errorsToShow);

    return errorsToShow;
  };

  /**
   * Save daraft application.
   *
   * @return {boolean} - Submit success indicator
   */
  const saveDraft = () => {
    const data = formRef.current?.state.formData;

    return submitData(data);
  };

  return (
    <>
      <ErrorsList />
      <Stepper formRef={formRef} />
      <div className='form-wrapper'>
        <StaticStepsContainer
          formRef={formRef}
          schema={schema}
        />
        <Form
          className='grants-react-form webform-submission-form'
          formData={initialFormData}
          method='POST'
          noHtml5Validate
          onChange={browserCacheData}
          onError={onError}
          onSubmit={(data, event: React.FormEvent<HTMLFormElement>) => {
            event.preventDefault();

            const passes = formRef.current?.validateForm();

            if (passes) {
              submitData(data.formData, true);
            }
          }}
          readonly={submitStatus !== SubmitStates.unsubmitted}
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
          <FormActions
            saveDraft={saveDraft}
            validatePartialForm={validatePartialForm}
          />
        </Form>
      </div>
    </>
  );
}

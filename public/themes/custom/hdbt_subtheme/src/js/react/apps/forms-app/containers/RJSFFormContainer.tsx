import { ErrorTransformer, RJSFSchema, RJSFValidationError, RegistryWidgetsType, UiSchema } from '@rjsf/utils';
import { useAtomValue, useSetAtom, WritableAtom } from 'jotai';
import { useAtomCallback } from 'jotai/utils';
import { useDebounceCallback } from 'usehooks-ts';
import Form, { getDefaultRegistry, IChangeEvent } from '@rjsf/core';
import React, { createRef, useCallback } from 'react';
import { customizeValidator } from '@rjsf/validator-ajv8';

import { ArrayFieldTemplate, ObjectFieldTemplate, RemoveButtonTemplate } from '../components/Templates';
import { ErrorsList } from '../components/ErrorsList';
import { FileInput } from '../components/FileInput';
import { FormActions } from '../components/FormActions/FormActions';
import { getCurrentStepAtom, getReachedStepAtom, getStepsAtom, getSubmitStatusAtom, setErrorsAtom } from '../store';
import { keyErrorsByStep } from '../utils';
import { StaticStepsContainer } from './StaticStepsContainer';
import { Stepper } from '../components/Stepper';
import { SubmitStates } from '../enum/SubmitStates';
import { TextArea, TextInput, SelectWidget, AddressSelect, BankAccountSelect, CommunityOfficialsSelect } from '../components/Input';
import { localizeErrors } from '../localizeErrors';
import { TextParagraph } from '../components/TextParagraph';
import { SubmittedForm } from '../components/SubmittedForm';
import { Terms } from '../components/Terms';

const widgets: RegistryWidgetsType = {
  'address': AddressSelect,
  'bank_account': BankAccountSelect,
  'community_officials': CommunityOfficialsSelect,
  EmailWidget: TextInput,
  SelectWidget,
  TextareaWidget: TextArea,
  TextWidget: TextInput,
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
  formDataAtom,
  saveDraft,
  schema,
  submitData,
  uiSchema,
}: {
  formDataAtom: WritableAtom<any, [update: unknown], any>;
  saveDraft: (data: any) => void;
  schema: RJSFSchema;
  submitData: (data: IChangeEvent) => void;
  uiSchema: UiSchema;
}) => {
  const setFormData = useSetAtom(formDataAtom)
  const submitStatus = useAtomValue(getSubmitStatusAtom);
  const steps = useAtomValue(getStepsAtom);
  const formRef = createRef<Form>();
  const readCurrentStep = useAtomCallback(
    useCallback(get =>  get(getCurrentStepAtom), [])
  );
  const readReachedStep = useAtomCallback(
    useCallback(get => get(getReachedStepAtom), [])
  );
  const readFormData = useAtomCallback(
    useCallback(get => get(formDataAtom), [])
  );
  const setErrors = useSetAtom(setErrorsAtom);

  const browserCacheData = useDebounceCallback(
    (data: IChangeEvent) => {
      setFormData(data.formData);
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
      .filter(([index]) => index <= reachedStep)
      .filter(([index, error]) => {
        const { name } = error;
        return name !== 'type';
      })
      .map(([index, error]) => error);
    setErrors(errorsToShow);

    return errorsToShow;
  };

  const readonly = submitStatus !== SubmitStates.DRAFT

  return <>
      {
        !readonly && <>
          <ErrorsList />
          <Stepper formRef={formRef} />
        </>
      }
      <div className='form-wrapper'>
        {readonly ?
          <SubmittedForm formData={readFormData()} schema={schema} /> :
          <StaticStepsContainer
            formDataAtom={formDataAtom}
            schema={schema}
          />
        }
        <Form
          className='grants-react-form webform-submission-form'
          fields={{
            ...getDefaultRegistry().fields,
            atvFile: FileInput,
            textParagraph: TextParagraph,
          }}
          formData={readFormData() || {}}
          liveValidate={false}
          method='POST'
          noHtml5Validate
          onChange={browserCacheData}
          onError={onError}
          onSubmit={async (data, event: React.FormEvent<HTMLFormElement>) => {
            event.preventDefault();

            const passes = formRef.current?.validateForm();

            if (passes) {
              submitData(data.formData);
            }
          }}
          readonly={readonly}
          ref={formRef}
          schema={schema} 
          showErrorList={false}
          templates={{
            ArrayFieldTemplate,
            ButtonTemplates: {
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
          validator={customizeValidator({}, localizeErrors)}
          widgets={widgets}
        >
          <Terms />
          {!readonly && <FormActions
              saveDraft={() => saveDraft(readFormData())}
              validatePartialForm={validatePartialForm}
            />
          }
        </Form>
      </div>
    </>;
}

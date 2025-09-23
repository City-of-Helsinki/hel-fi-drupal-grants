import { CustomValidator, ErrorTransformer, RJSFSchema, RJSFValidationError, RegistryWidgetsType, UiSchema } from '@rjsf/utils';
import { useAtomValue, useSetAtom, WritableAtom } from 'jotai';
import { useAtomCallback } from 'jotai/utils';
import { useDebounceCallback } from 'usehooks-ts';
import Form, { getDefaultRegistry, IChangeEvent } from '@rjsf/core';
import React, { createRef, useCallback, useMemo, useState } from 'react';
import { customizeValidator } from '@rjsf/validator-ajv8';
import { useTranslation } from 'react-i18next';

import { ArrayFieldTemplate, ObjectFieldTemplate, RemoveButtonTemplate } from '../components/Templates';
import { ErrorsList } from '../components/ErrorsList';
import { FileInput } from '../components/FileInput';
import { FormActions } from '../components/FormActions/FormActions';
import { getCurrentStepAtom, getReachedStepAtom, getStepsAtom, getSubmitStatusAtom, setErrorsAtom } from '../store';
import { findFieldsOfType, keyErrorsByStep } from '../utils';
import { StaticStepsContainer } from './StaticStepsContainer';
import { Stepper } from '../components/Stepper';
import { SubmitStates } from '../enum/SubmitStates';
import { TextArea, TextInput, SelectWidget, AddressSelect, BankAccountSelect, CommunityOfficialsSelect, RadioWidget } from '../components/Input';
import { localizeErrors } from '../localizeErrors';
import { TextParagraph } from '../components/Fields/TextParagraph';
import { SubmittedForm } from '../components/SubmittedForm';
import { Terms } from '../components/Terms';
import { SubventionTable } from '../components/Fields/SubventionTable';
import { InvalidSchemaError } from '../errors/InvalidSchemaError';

const widgets: RegistryWidgetsType = {
  'address': AddressSelect,
  'bank_account': BankAccountSelect,
  'community_officials': CommunityOfficialsSelect,
  EmailWidget: TextInput,
  RadioWidget,
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
  const { t } = useTranslation();
  const [invalidSchemaError, setInvalidSchemaError] = useState<InvalidSchemaError | null>(null);
  const subventionFields = useMemo(() => Array.from(findFieldsOfType(uiSchema, 'subventionTable')), [uiSchema]); 
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

  if (invalidSchemaError) {
    throw invalidSchemaError;
  }

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

  const filterErrorsByReachedStep = (errors) => {
    const reachedStep = readReachedStep();

    return errors
      .filter(([index]) => index <= reachedStep)
      .filter(([index, error]) => {
        const { name } = error;
        return name !== 'type';
      })
      .map(([index, error]) => error);
  };

  /**
   * Transforms RJSF-generated errors.
   *
   * @param {array} errors - RJSF validation errors
   * @return {array} - modified and filtered errors
   */
  const transformErrors: ErrorTransformer = (errors) => {
    if (
      Array.isArray(errors) &&
      errors[0]?.stack.includes('schema is invalid')
    ) {
      setInvalidSchemaError(new InvalidSchemaError(errors[0].stack));
      return;
    }

    const errorsToShow = filterErrorsByReachedStep(keyErrorsByStep(errors, steps));  
    setErrors(errorsToShow);

    return errorsToShow;
  };

/**
 * Custom validation rules for RJSF form.
 * 
 * @param {object} formData - Form data
 * @param {object} errors - Form errors
 * @param {object} _uiSchema - Form Ui Schema
 * 
 * @return {object} - Form errors
 */
  const customValidate: CustomValidator = (formData, errors, _uiSchema) => {
    const newErrors = [];

    subventionFields.forEach(field => {
      const values = field.split('.').reduce((acc, curr) => acc && acc[curr], formData);
      const _field = field.split('.').reduce((acc, curr) => acc && acc[curr], errors);
      const hasValues = values ? Object.entries(values).reduce((acc, [key, curr]) => acc || Number(curr[1].value) > 0, false) : false;

      if (!hasValues) {
        _field.addError(t('subvention.greater_than_zero'));
        newErrors.push({
          property: `.${field}`,
          message: t('subvention.greater_than_zero'),
          schemaPath: `.${field}`,
        });
      }
    });

    const errorsToShow = filterErrorsByReachedStep(keyErrorsByStep(newErrors, steps));  
    setErrors(errorsToShow, true);

    return errors;
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
          customValidate={customValidate}
          fields={{
            ...getDefaultRegistry().fields,
            atvFile: FileInput,
            subventionTable: SubventionTable,
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

            if (readCurrentStep()[1].id !== 'preview') {
              return;
            }

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
              SubmitButton: () => null,
              MoveDownButton: () => null,
              MoveUpButton: () => null,
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

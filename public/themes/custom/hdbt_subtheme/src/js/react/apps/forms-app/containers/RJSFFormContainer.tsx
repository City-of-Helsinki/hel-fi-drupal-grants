import type {
  CustomValidator,
  ErrorTransformer,
  RJSFSchema,
  RJSFValidationError,
  RegistryWidgetsType,
  UiSchema,
} from '@rjsf/utils';
import { useAtomValue, useSetAtom, type WritableAtom } from 'jotai';
import { useAtomCallback } from 'jotai/utils';
import { useDebounceCallback } from 'usehooks-ts';
import Form, { getDefaultRegistry, type IChangeEvent } from '@rjsf/core';
import type { ReactNode, FormEvent } from 'react';
import { createRef, useCallback, useEffect, useState } from 'react';
import { customizeValidator } from '@rjsf/validator-ajv8';
import { useTranslation } from 'react-i18next';

import {
  AddressSelect,
  BankAccountSelect,
  CheckboxWidget,
  CommunityOfficialsSelect,
  DateWidget,
  RadioWidget,
  SelectWidget,
  TextArea,
  TextInput,
} from '../components/Input';
import { ArrayFieldTemplate, FieldTemplate, ObjectFieldTemplate, RemoveButtonTemplate } from '../components/Templates';
import { ErrorsList } from '../components/ErrorsList';
import { FileInput } from '../components/FileInput';
import { FormActions } from '../components/FormActions/FormActions';
import { FormSummary } from '../components/FormSummary';
import {
  getCurrentStepAtom,
  getReachedStepAtom,
  getStepsAtom,
  setErrorsAtom,
  getSubventionFieldsAtom,
  getRequiredFileFieldsAtom,
  isReadOnlyAtom,
  setStepAtom,
  isEmptyPreviewAtom,
} from '../store';
import { InvalidSchemaError } from '../errors/InvalidSchemaError';
import { isDraft, keyErrorsByStep } from '../utils';
import { StaticStepsContainer } from './StaticStepsContainer';
import { Stepper } from '../components/Stepper';
import { SubventionSum } from '../components/Fields/SubventionSum';
import { SubventionTable } from '../components/Fields/SubventionTable';
import { Terms } from '../components/Terms';
import { TextParagraph } from '../components/Fields/TextParagraph';
import { localizeErrors } from '../localizeErrors';
import { Notification, NotificationSize } from 'hds-react';
import type { RJSFFormData } from '../types/RJSFFormData';

const widgets: RegistryWidgetsType = {
  address: AddressSelect,
  bank_account: BankAccountSelect,
  community_officials: CommunityOfficialsSelect,
  DateWidget,
  EmailWidget: TextInput,
  RadioWidget,
  SelectWidget,
  TextareaWidget: TextArea,
  TextWidget: TextInput,
  CheckboxWidget,
};

const SubmitButton = () => null;
const MoveDownButton = () => null;
const MoveUpButton = () => null;
const FieldErrorTemplate = () => null;

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
  formDataAtom: WritableAtom<RJSFFormData, [update: unknown], RJSFFormData>;
  saveDraft: (data: RJSFFormData) => Promise<void>;
  schema: RJSFSchema;
  submitData: (data: IChangeEvent) => void;
  uiSchema: UiSchema;
}) => {
  const { t } = useTranslation();
  const [invalidSchemaError, setInvalidSchemaError] = useState<InvalidSchemaError | null>(null);

  useEffect(() => {
    if (!drupalSettings.grants_react_form.use_print) {
      return;
    }
    document.body.classList.add('webform-submission-data-preview-page', 'webform-print');
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        window.print();
        document.body.classList.remove('webform-submission-data-preview-page', 'webform-print');
        setTimeout(() => window.history.back(), 500);
      });
    });
  }, []);
  const subventionFields = useAtomValue(getSubventionFieldsAtom);
  const requiredFileFields = useAtomValue(getRequiredFileFieldsAtom);
  const setFormData = useSetAtom(formDataAtom);
  const steps = useAtomValue(getStepsAtom);
  const setStep = useSetAtom(setStepAtom);
  const readOnly = useAtomValue(isReadOnlyAtom);
  const formRef = createRef<Form>();
  const readCurrentStep = useAtomCallback(useCallback((get) => get(getCurrentStepAtom), []));
  const readReachedStep = useAtomCallback(useCallback((get) => get(getReachedStepAtom), []));
  const readFormData = useAtomCallback(useCallback((get) => get(formDataAtom), [formDataAtom]));
  const setErrors = useSetAtom(setErrorsAtom);
  const isEmptyPreview = useAtomValue(isEmptyPreviewAtom);

  const browserCacheData = useDebounceCallback((data: IChangeEvent) => {
    setFormData(data.formData);
  }, 2000);

  if (invalidSchemaError) {
    throw invalidSchemaError;
  }

  if (drupalSettings.grants_react_form.use_preview) {
    setStep(steps.size - 2);
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

    const currentPageErrors = keyedErrors.filter(([index]) => index === currentStepIndex);

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

  const filterErrorsByReachedStep = (errors: [number, RJSFValidationError][]) => {
    const reachedStep = readReachedStep();

    return errors.filter(([index]) => index <= reachedStep).map(([, error]) => error);
  };

  /**
   * Transforms RJSF-generated errors.
   *
   * @param {array} errors - RJSF validation errors
   * @return {array} - modified and filtered errors
   */
  const transformErrors: ErrorTransformer = (errors) => {
    if (Array.isArray(errors) && errors[0]?.stack.includes('schema is invalid')) {
      setInvalidSchemaError(new InvalidSchemaError(errors[0].stack));
      return [];
    }

    const prefilteredErrors = errors.filter((error) => error.params?.type !== 'null');

    const errorsToShow = filterErrorsByReachedStep(keyErrorsByStep(prefilteredErrors, steps));
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
    const newErrors: RJSFValidationError[] = [];

    subventionFields.forEach((field) => {
      const values = field.split('.').reduce((acc: RJSFFormData, curr) => acc?.[curr], formData) as
        | Record<string, [unknown, { value: unknown }]>
        | undefined;
      const _field = field.split('.').reduce((acc: RJSFFormData, curr) => acc?.[curr], errors) as
        | { addError: (msg: string) => void }
        | undefined;
      const hasValues = values
        ? Object.entries(values).reduce((acc, [, curr]) => acc || Number(curr[1].value) > 0, false)
        : false;

      if (_field && !hasValues) {
        _field.addError(t('subvention.greater_than_zero'));
        newErrors.push({
          property: `.${field}`,
          message: t('subvention.greater_than_zero'),
          schemaPath: `.${field}`,
          stack: `.${field} ${t('subvention.greater_than_zero')}`,
        });
      }
    });

    const reachedStep = readReachedStep();
    requiredFileFields.forEach((field) => {
      const stepId = field.split('.')[0];
      const stepEntry = Array.from(steps).find(([, step]) => step.id === stepId);
      const stepIndex = stepEntry?.[0] ?? 0;

      const fileData = field.split('.').reduce((acc: RJSFFormData, curr) => acc?.[curr], formData);
      const isFulfilled = fileData?.fileName || fileData?.isDeliveredLater || fileData?.isIncludedInOtherFile;

      if (!isFulfilled) {
        newErrors.push({
          property: `.${field}`,
          message: t('file.required'),
          schemaPath: `.${field}`,
          stack: `.${field} ${t('file.required')}`,
        });

        // Only show inline error on the field itself if the user has reached this step
        if (stepIndex <= reachedStep) {
          const _field = field.split('.').reduce((acc: RJSFFormData, curr) => acc?.[curr], errors) as
            | { addError: (msg: string) => void }
            | undefined;
          _field?.addError(t('file.required'));
        }
      }
    });

    const errorsToShow = filterErrorsByReachedStep(keyErrorsByStep(newErrors, steps));
    setErrors(errorsToShow, true);

    return errors;
  };

  const getFormTopArea = () => {
    const components: ReactNode[] = [];

    if (isEmptyPreview) {
      components.push(
        <Notification
          className='hdbt-form--notification empty-preview-notification'
          key='preview-notification'
          label={Drupal.t('Preview mode', {}, { context: 'grants_webform_print' })}
          size={NotificationSize.Small}
          type='alert'
        >
          {Drupal.t(
            'This printout is only for previewing the application and cannot be used when applying for a grant',
            {},
            { context: 'grants_webform_print' },
          )}
        </Notification>,
      );
    }

    if (!isDraft()) {
      components.push(<FormSummary key='summary' />);
    }

    if (!readOnly && !isEmptyPreview) {
      components.push(<StaticStepsContainer key='static-steps' formDataAtom={formDataAtom} schema={schema} />);
    }

    return components;
  };

  return (
    <>
      {!readOnly && (
        <>
          <ErrorsList />
          <Stepper formRef={formRef} />
        </>
      )}
      <div className='form-wrapper'>
        {getFormTopArea()}
        <Form
          className='grants-form'
          customValidate={customValidate}
          fields={{
            ...getDefaultRegistry().fields,
            atvFile: FileInput,
            subventionTable: SubventionTable,
            subventionSum: SubventionSum,
            textParagraph: TextParagraph,
          }}
          formData={readFormData() || {}}
          liveValidate={false}
          method='POST'
          noHtml5Validate
          onChange={browserCacheData}
          onError={onError}
          onSubmit={async (data, event: FormEvent<HTMLFormElement>) => {
            event.preventDefault();

            if (readCurrentStep()[1].id !== 'preview') {
              return;
            }

            const passes = formRef.current?.validateForm();

            if (passes) {
              submitData(data.formData);
            }
          }}
          readonly={readOnly}
          ref={formRef}
          schema={schema}
          showErrorList={false}
          templates={{
            ArrayFieldTemplate,
            ButtonTemplates: {
              RemoveButton: RemoveButtonTemplate,
              SubmitButton,
              MoveDownButton,
              MoveUpButton,
            },
            FieldErrorTemplate,
            FieldTemplate,
            ObjectFieldTemplate,
          }}
          transformErrors={transformErrors}
          uiSchema={{ ...uiSchema, 'ui:globalOptions': { label: false } }}
          validator={customizeValidator(
            { ajvOptionsOverrides: { allErrors: true, coerceTypes: false } },
            localizeErrors,
          )}
          widgets={widgets}
        >
          {!drupalSettings.grants_react_form.use_preview && !isEmptyPreview && (
            <>
              <Terms />
              <FormActions saveDraft={() => saveDraft(readFormData())} validatePartialForm={validatePartialForm} />
            </>
          )}
        </Form>
      </div>
    </>
  );
};

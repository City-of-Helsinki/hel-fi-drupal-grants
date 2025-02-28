import { RJSFSchema, RJSFValidationError, UiSchema } from '@rjsf/utils';
import { atom } from 'jotai';
import { getIndicesWithErrors, keyErrorsByStep } from './utils';

export type FormStep = {
  id: string;
  label: string;
};

type GrantsProfile = {
  companyNameShort: string;
  companyName: string;
  companyHome: string;
  companyHomePage: string;
  companyStatus: string;
  companyStatusSpecial: string;
  businessPurpose: string;
  foundingYear: string;
  registrationDate: string;
  officials: Array<any>;
  addresses: Array<{
    address_id: string;
    street: string;
    postCode: string;
    city: string;
    country: string;
  }>;
  bankAccounts: Array<{
      bankAccount: string;
      confirmationFile: string;
      bank_account_id: string;
      ownerName?: string;
      ownerSsn?: string;
  }>,
  businessId: string;
};

type FormState = {
  currentStep: [number, FormStep];
  errors: Array<[number, RJSFValidationError]>;
  reachedStep: number;
}

type FormConfig = {
  grantsProfile: GrantsProfile,
  schema: RJSFSchema;
  uiSchema: UiSchema;
  translations: {
    [key in 'fi'|'sv'|'en']: {
      [key: string]: string;
    };
  };
};

type ResponseData = Omit<FormConfig, 'grantsProfile' | 'uiSchema'> & {
  grants_profile: GrantsProfile;
  ui_schema: UiSchema;
};

const buildFormSteps = ({
  schema: {
    properties
  }
}: any) => {
  const steps = new Map();
  Object.entries(properties).forEach((property: any, index: number) => {
    const [key, value] = property;

    steps.set(index, {
      id: key,
      label: value.title
    });
  });

  const previewIndex = steps.size;
  steps.set(previewIndex, {
    id: 'preview',
    label: Drupal.t('Confirm, preview and submit'),
  });
  steps.set(previewIndex + 1, {
    id: 'ready',
    label: Drupal.t('Ready'),
  });

  return steps;
};

export const formConfigAtom = atom<FormConfig|undefined>();
export const formStateAtom = atom<FormState|undefined>();
export const formStepsAtom = atom<Map<number, FormStep>|undefined>();
export const initializeFormAtom = atom(null, (_get, _set, formConfig: ResponseData) => {
  const { grants_profile: grantsProfile, ui_schema: uiSchema, ...rest } = formConfig;
  const steps = buildFormSteps(formConfig);
  _set(formStepsAtom, state => steps);
  _set(formConfigAtom, (state) => ({
    grantsProfile,
    uiSchema,
    ...rest,
  }));
  _set(formStateAtom, (state) => ({
      currentStep: [0, steps.get(0)],
      errors: [],
      reachedStep: 0,
    }));
});
export const getFormConfigAtom = atom(_get => {
  const config = _get(formConfigAtom);

  if (!config) {
    throw new Error('Trying to read form config before initialization.');
  }

  return config;
});
const getFormStateAtom  = atom(_get => {
  const state = _get(formStateAtom);

  if (!state) {
    throw new Error('Trying to read form state before initialization.');
  }

  return state;
});
export const getStepsAtom = atom(_get => {
  const steps = _get(formStepsAtom);

  if (!steps) {
    throw new Error('Trying to read steps before form initialization.')
  }

  return steps;
});
export const getCurrentStepAtom = atom(_get => {
  const currentState = _get(getFormStateAtom);

  return currentState.currentStep;
});
export const setStepAtom = atom(null, (_get, _set, index: number) => {
  const steps = _get(formStepsAtom);
  const currentState = _get(getFormStateAtom);

  const step = steps?.get(index);
  if (!step) {
    throw new Error(`Index ${index} does not exist in defined steps for the form.`);
  }

  _set(formStateAtom, _state => ({
      ...currentState,
      reachedStep: currentState?.reachedStep > index ? currentState?.reachedStep : index,
      currentStep: [index, step],
    })
  );
});
export const getReachedStepAtom = atom(_get => {
  const { reachedStep } = _get(getFormStateAtom);

  return reachedStep;
});
export const setErrorsAtom = atom(null, (_get, _set, errors: RJSFValidationError[]) => {
  const steps = _get(formStepsAtom);
  const currentState = _get(getFormStateAtom);

  _set(formStateAtom, state => ({
    ...currentState,
    errors: keyErrorsByStep(errors, steps)
  }));
});
export const getErrorsAtom = atom(_get => {
  const { errors } = _get(getFormStateAtom);

  return errors;
});
export const getErrorPageIndicesAtom = atom(_get => {
  const { errors } = _get(getFormStateAtom);

  return errors.map(([index]) => index);
});

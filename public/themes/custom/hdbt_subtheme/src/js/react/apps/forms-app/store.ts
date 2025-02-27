import { RJSFSchema, RJSFValidationError, UiSchema } from '@rjsf/utils';
import { atom } from 'jotai';

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
  errorPageIndices: number[];
  errors: RJSFValidationError[];
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

const getIndicesWithErrors = (
  errors: RJSFValidationError[]|undefined,
  steps?: Map<number, FormStep>,
) => {
  if (!steps || !errors || !errors?.length) {
    return [];
  }

  const errorIndices: number[] = [];
  const propertyParentKeys: string[] = [];
  const regex = new RegExp('^\.([^\.]+)');
  errors.forEach(error => {
    let match = error?.property?.match(regex)?.[0];

    if (match) {
      propertyParentKeys.push(match.split('.')[1]);
    }
  });
  Array.from(steps).forEach(([index, step]) => {
    if (propertyParentKeys.includes(step.id)) {
      errorIndices.push(index);
    }
  });

  return errorIndices;
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
      errorPageIndices: [],
      errors: [],
    }));
});
export const getFormConfigAtom  = atom(_get => {
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
export const getCurrentStepAtom = atom(_get => {
  const currentState = _get(formStateAtom);

  if (!currentState?.currentStep) {
    throw new Error('Current step is not set. Form has not been initalized.')
  }

  return currentState.currentStep;
});
export const setStepAtom = atom(null, (_get, _set, index: number) => {
  const steps = _get(formStepsAtom);

  const step = steps?.get(index);
  if (!step) {
    throw new Error(`Index ${index} does not exist in defined steps for the form.`);
  }

  _set(formStateAtom, state => state ? {
      ...state,
      currentStep: [index, step],
    } : state);
});
export const setErrorsAtom = atom(null, (_get, _set, errors: RJSFValidationError[]) => {
  const steps = _get(formStepsAtom);
  const currentState = _get(getFormStateAtom);

  _set(formStateAtom, state => ({
    ...currentState,
    errorPageIndices: getIndicesWithErrors(errors, steps),
    errors,
  }));
});
export const getErrorsAtom = atom(_get => {
  const {errors, errorPageIndices} = _get(getFormStateAtom);

  return {
    errors,
    errorPageIndices,
  };
});

import { RJSFSchema, RJSFValidationError, UiSchema } from '@rjsf/utils';
import { atom } from 'jotai';
import { NotificationProps } from 'hds-react';
import { keyErrorsByStep } from './utils';
import { SubmitState, SubmitStates } from './enum/SubmitStates';

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

export type FormState = {
  currentStep: [number, FormStep];
  errors: Array<[number, RJSFValidationError]>;
  reachedStep: number;
}

type FormConfig = {
  applicationNumber?: string;
  grantsProfile: GrantsProfile;
  token: string;
  schema: RJSFSchema;
  settings: {
    [key: string]: string;
  }
  submitState: SubmitState;
  translations: {
    [key in 'fi'|'sv'|'en']: {
      [key: string]: string;
    };
  };
  uiSchema: UiSchema;
};

type ResponseData = Omit<FormConfig, 'grantsProfile' | 'uiSchema' | 'submit_state'> & {
  grants_profile: GrantsProfile;
  submit_state: SubmitState;
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
  const {
    grants_profile: grantsProfile,
    submit_state: submitState,
    ui_schema: uiSchema,
    ...rest
  } = formConfig;
  const steps = buildFormSteps(formConfig);
  _set(formStepsAtom, state => steps);
  _set(formConfigAtom, (state) => ({
    grantsProfile,
    ...rest,
    uiSchema,
    submitState: submitState || SubmitStates.unsubmitted,
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
export const getAddressesAtom = atom(_get => {
  const { grantsProfile } = _get(getFormConfigAtom);

  return grantsProfile.addresses;
});
export const getAccountsAtom = atom(_get => {
  const { grantsProfile } = _get(getFormConfigAtom);

  return grantsProfile.bankAccounts;
});
export const getOfficialsAtom = atom(_get => {
  const { grantsProfile } = _get(getFormConfigAtom);

  return grantsProfile.officials;
});
export const getSubmitStatusAtom = atom(_get => {
  const { submitState } = _get(getFormConfigAtom);

  return submitState;
});
export const setSubmitStatusAtom = atom(null, (_get, _set, submitState: SubmitState) => {
  const formConfig = _get(getFormConfigAtom);

  _set(formConfigAtom, state => ({
    ...formConfig,
    submitState,
  }));
});
export const getApplicationNumberAtom = atom(_get => {
  const { applicationNumber } = _get(getFormConfigAtom);

  return applicationNumber;
});
export const setApplicationNumberAtom = atom(null, (_get, _set, applicationNumber: string) => {
  const formConfig = _get(getFormConfigAtom);

  const params = new URLSearchParams(window.location.search);
  params.set('application_number', applicationNumber);
  window.history.replaceState(null, '', `${window.location.pathname}?${params.toString()}`);

  _set(formConfigAtom, state => ({
    ...formConfig,
    applicationNumber,
  }));
});

type SystemNotification = Pick<NotificationProps, 'label'|'type'|'children'>
export const systemNotificationsAtom = atom<SystemNotification[]>([]);
export const pushNotificationAtom = atom(null, (_get, _set, notification: SystemNotification) => {
  _set(systemNotificationsAtom, state => [...state, notification]);
});
export const shiftNotificationsAtom = atom(null, (_get, _set) => {
  _set(systemNotificationsAtom, state => state.slice(1));
});
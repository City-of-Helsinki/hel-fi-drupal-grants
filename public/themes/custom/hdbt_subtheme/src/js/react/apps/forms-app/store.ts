import { ReactNode } from 'react';
import { RJSFSchema, RJSFValidationError, UiSchema } from '@rjsf/utils';
import { atom } from 'jotai';
import { keyErrorsByStep } from './utils';
import { SubmitStates } from './enum/SubmitStates';
import { getUrlParts } from './testutils/Helpers';

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
  reachedStep: number;
}

type FormConfig = {
  applicationNumber: string;
  grantsProfile: GrantsProfile;
  persistedData: any;
  token: string;
  schema: RJSFSchema;
  settings: {
    [key: string]: string;
  }
  submitState: string;
  translations: {
    [key in 'fi'|'sv'|'en']: {
      [key: string]: string;
    };
  };
  uiSchema: UiSchema;
};

type ResponseData = Omit<FormConfig, 'grantsProfile' | 'uiSchema' | 'submit_state'> & {
  applicationNumber: string;
  grants_profile: GrantsProfile;
  status: string;
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
    label: Drupal.t('Confirm, preview and submit', {}, {context: 'Grants application: Steps'}),
  });
  steps.set(previewIndex + 1, {
    id: 'ready',
    label: Drupal.t('Ready', {}, {context: 'Grants application: Steps'}),
  });

  return steps;
};

export const createFormDataAtom = (key: string, initialValue: any) => {
  const getInitialValue = () => {
    const item = sessionStorage.getItem(key);
    if (item !== null) {
      return JSON.parse(item);
    }
    return {};
  }

  // @todo use timestamp to determine which data to use. For now, always prefer server.
  if (initialValue) {
    sessionStorage.setItem(key, JSON.stringify(initialValue));
  }

  const baseAtom = atom(getInitialValue());
  const derivedAtom = atom(
    (get) => get(baseAtom),
    (get, set, update) => {
      const newValue = typeof update === 'function' ? update(get(baseAtom)) : update;
      set(baseAtom, newValue);
      sessionStorage.setItem(key, JSON.stringify(newValue));
    },
  );

  return derivedAtom;
};

export const formStateAtom = atom<FormState|undefined>();
export const formConfigAtom = atom<FormConfig|undefined>();
export const formStepsAtom = atom<Map<number, FormStep>|undefined>();
export const errorsAtom = atom<Array<[number, RJSFValidationError]>>([]);
export const initializeFormAtom = atom(null, (_get, _set, formConfig: ResponseData) => {
  const {
    grants_profile: grantsProfile,
    status,
    ui_schema: uiSchema,
    ...rest
  } = formConfig;
  const steps = buildFormSteps(formConfig);
  _set(formStepsAtom, state => steps);
  _set(formConfigAtom, (state) => ({
    grantsProfile,
    ...rest,
    uiSchema,
    submitState: status || SubmitStates.DRAFT,
  }));
  _set(formStateAtom, (state) => ({
      currentStep: [0, steps.get(0)],
      reachedStep: 0,
    }));

  // Make sure application number is set in url params.
  const { applicationNumber } = formConfig;
  _set(setApplicationNumberAtom, applicationNumber);
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
export const finalAcceptanceAtom = atom<boolean>(false);
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
  _set(finalAcceptanceAtom, false);
});
export const getReachedStepAtom = atom(_get => {
  const { reachedStep } = _get(getFormStateAtom);

  return reachedStep;
});
export const setErrorsAtom = atom(null, (_get, _set, errors: RJSFValidationError[], additiveOnly = false) => {
  const steps = _get(formStepsAtom);

  if (!additiveOnly) {
    _set(errorsAtom, state => keyErrorsByStep(errors, steps));
    return;
  }

  _set(errorsAtom, state => [...state, ...keyErrorsByStep(errors, steps)]);
});
export const getErrorPageIndicesAtom = atom(_get => {
  const errors = _get(errorsAtom);

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
export const setSubmitStatusAtom = atom(null, (_get, _set, submitState: string) => {
  const formConfig = _get(getFormConfigAtom);

  _set(formConfigAtom, state => ({
    ...formConfig,
    submitState,
  }));
});
export const getSchemasAtom = atom(_get => {
  const { schema, uiSchema } = _get(getFormConfigAtom);

  return {
    schema,
    uiSchema,
  };
});
export const getApplicationNumberAtom = atom(_get => {
  const { applicationNumber } = _get(getFormConfigAtom);

  return applicationNumber;
});
export const getTranslationsAtom = atom(_get => {
  const { translations } = _get(getFormConfigAtom);

  return translations;
})
export const setApplicationNumberAtom = atom(null, (_get, _set, applicationNumber: string) => {
  const formConfig = _get(getFormConfigAtom);

  const currentParts = getUrlParts();
  currentParts[4] = applicationNumber;
  const currentUrl = new URL(window.location.href);
  currentUrl.pathname = currentParts.join('/');
  window.history.replaceState(null, '', currentUrl.toString());

  _set(formConfigAtom, state => ({
    ...formConfig,
    applicationNumber,
  }));
});
export type SystemNotification = {
  children: string| ReactNode,
  label: string | ReactNode,
  type: 'info' | 'error' | 'alert' | 'success',
}
export const systemNotificationsAtom = atom<SystemNotification[]>([]);
export const pushNotificationAtom = atom(null, (_get, _set, notification: SystemNotification) => {
  _set(systemNotificationsAtom, state => [...state, notification]);
});
export const shiftNotificationsAtom = atom(null, (_get, _set) => {
  _set(systemNotificationsAtom, state => state.slice(1));
});

type avus2Data = {
  attachmentsInfo: {
    attachmentsArray: Array<{
      ID: string;
      label: string;
      value: string;
      valueType: string;
    }[]>;
    generalInfoArray: Array<{
      ID: string;
      label: string;
      valueType: string;
    }>
  };
  events: Array<{
    caseId: string;
    eventCode: number;
    eventCreated: string;
    eventDescription: string;
    eventID: string;
    eventSource: string;
    eventTarget: string;
    eventType: string;
    timeUpdated: string;
  }>
  messages: any[];
  statusUpdates: Array<{
    caseId: string;
    citizenCaseStatus: string;
    eventType: string;
    eventCode: 1;
    eventSource: string;
    timeUpdated: string;
    timeCreated: string;
  }>
};
export const avus2DataAtom = atom<avus2Data|null>();
export const shouldRenderPreviewAtom = atom(_get => {
  const { submitState } = _get(getFormConfigAtom);
  const { currentStep } = _get(getFormStateAtom);

  return submitState !== SubmitStates.DRAFT || currentStep[1].id === 'preview';
});

// biome-ignore-all lint/suspicious/noExplicitAny: @todo UHF-12501
// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
import { atom } from 'jotai';
import { DateTime } from 'luxon';
import type { ReactNode } from 'react';
import type { RJSFSchema, RJSFValidationError, UiSchema } from '@rjsf/utils';

import { findFieldsOfType, keyErrorsByStep } from './utils';
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
  }>;
  businessId: string;
};

export type FormState = {
  currentStep: [number, FormStep];
  reachedStep: number;
};

type FormConfig = {
  applicationNumber: string;
  grantsProfile: GrantsProfile;
  persistedData: any;
  token: string;
  schema: RJSFSchema;
  settings: {
    [key: string]: string;
  };
  submitState: string;
  subventionFields: string[];
  translations: {
    [key in 'fi' | 'sv' | 'en']: {
      [key: string]: string;
    };
  };
  uiSchema: UiSchema;
};

type ResponseData = Omit<
  FormConfig,
  'grantsProfile' | 'uiSchema' | 'submit_state'
> & {
  applicationNumber: string;
  grants_profile: GrantsProfile;
  status: string;
  ui_schema: UiSchema;
};

const buildFormSteps = ({ schema: { properties } }: any) => {
  const steps = new Map();
  Object.entries(properties).forEach((property: any, index: number) => {
    const [key, value] = property;

    steps.set(index, {
      id: key,
      label: `${index + 1}. ${value.title}`,
    });
  });

  const previewIndex = steps.size;
  const readyIndex = previewIndex + 1;
  steps.set(previewIndex, {
    id: 'preview',
    label: `${previewIndex + 1}. ${Drupal.t('Confirm, preview and submit', {}, { context: 'Grants application: Steps' })}`,
  });
  steps.set(readyIndex, {
    id: 'ready',
    label: `${readyIndex + 1}. ${Drupal.t('Ready', {}, { context: 'Grants application: Steps' })}`,
  });

  return steps;
};

export const createFormDataAtom = (
  key: string,
  initialValue: any,
  timestamp?: number,
) => {
  initialValue =
    initialValue && Array.isArray(initialValue) && !initialValue.length
      ? {}
      : initialValue;

  const getInitialValue = () => {
    const sessionItem = JSON.parse(sessionStorage.getItem(key));

    if (
      !sessionItem ||
      // Handle old style session data without timestamp.
      // @todo Remove this check after a few months of deployment.
      !sessionItem?.timestamp
    ) {
      sessionStorage.setItem(
        key,
        JSON.stringify({
          timestamp: timestamp || Math.floor(Date.now() / 1000),
          data: initialValue,
        }),
      );
      return initialValue;
    }

    const { timestamp: sessionTimeStamp, data: sessionData } = sessionItem;
    const sessionTime = DateTime.fromMillis(Number(sessionTimeStamp) * 1000);
    const serverTime = timestamp
      ? DateTime.fromMillis(Number(timestamp) * 1000)
      : null;

    if (serverTime && sessionTime < serverTime) {
      return initialValue;
    }

    return sessionData ?? {};
  };

  const baseAtom = atom(getInitialValue());
  const derivedAtom = atom(
    (get) => get(baseAtom),
    (get, set, update) => {
      const newValue =
        typeof update === 'function' ? update(get(baseAtom)) : update;
      set(baseAtom, newValue);
      sessionStorage.setItem(
        key,
        JSON.stringify({
          timestamp: Math.floor(Date.now() / 1000),
          data: newValue,
        }),
      );
    },
  );

  return derivedAtom;
};
export const formDataAtomRef = atom<any>(null);
export const formStateAtom = atom<FormState | undefined>();
export const formConfigAtom = atom<FormConfig | undefined>();
export const formStepsAtom = atom<Map<number, FormStep> | undefined>();
export const errorsAtom = atom<Array<[number, RJSFValidationError]>>([]);
export const initializeFormAtom = atom(
  null,
  (_get, _set, formConfig: ResponseData) => {
    const {
      grants_profile: grantsProfile,
      status,
      ui_schema: uiSchema,
      ...rest
    } = formConfig;
    const steps = buildFormSteps(formConfig);
    _set(formStepsAtom, (state) => steps);
    _set(formConfigAtom, (state) => ({
      grantsProfile,
      ...rest,
      uiSchema,
      submitState: status || SubmitStates.DRAFT,
      subventionFields: Array.from(
        findFieldsOfType(uiSchema, 'subventionTable'),
      ),
    }));
    _set(formStateAtom, (state) => ({
      currentStep: [0, steps.get(0)],
      reachedStep: 0,
    }));

    // Make sure application number is set in url params.
    const { applicationNumber } = formConfig;
    _set(setApplicationNumberAtom, applicationNumber);
  },
);
export const getFormConfigAtom = atom((_get) => {
  const config = _get(formConfigAtom);

  if (!config) {
    throw new Error('Trying to read form config before initialization.');
  }

  return config;
});
const getFormStateAtom = atom((_get) => {
  const state = _get(formStateAtom);

  if (!state) {
    throw new Error('Trying to read form state before initialization.');
  }

  return state;
});
export const finalAcceptanceAtom = atom<boolean>(false);
export const getStepsAtom = atom((_get) => {
  const steps = _get(formStepsAtom);

  if (!steps) {
    throw new Error('Trying to read steps before form initialization.');
  }

  return steps;
});
export const getCurrentStepAtom = atom((_get) => {
  const currentState = _get(getFormStateAtom);

  return currentState.currentStep;
});
export const setStepAtom = atom(null, (_get, _set, index: number) => {
  const steps = _get(formStepsAtom);
  const currentState = _get(getFormStateAtom);

  const step = steps?.get(index);
  if (!step) {
    throw new Error(
      `Index ${index} does not exist in defined steps for the form.`,
    );
  }

  _set(formStateAtom, (_state) => ({
    ...currentState,
    reachedStep:
      currentState?.reachedStep > index ? currentState?.reachedStep : index,
    currentStep: [index, step],
  }));
  _set(finalAcceptanceAtom, false);
});
export const getReachedStepAtom = atom((_get) => {
  const { reachedStep } = _get(getFormStateAtom);

  return reachedStep;
});
export const setErrorsAtom = atom(
  null,
  (_get, _set, errors: RJSFValidationError[], additiveOnly = false) => {
    const steps = _get(formStepsAtom);

    if (!additiveOnly) {
      _set(errorsAtom, (state) => keyErrorsByStep(errors, steps));
      return;
    }

    _set(errorsAtom, (state) => [...state, ...keyErrorsByStep(errors, steps)]);
  },
);
export const getErrorPageIndicesAtom = atom((_get) => {
  const errors = _get(errorsAtom);

  return errors.map(([index]) => index);
});
export const getProfileAtom = atom((_get) => {
  const { grantsProfile } = _get(getFormConfigAtom);

  return grantsProfile;
});
export const getAddressesAtom = atom((_get) => {
  const { grantsProfile } = _get(getFormConfigAtom);

  return grantsProfile.addresses;
});
export const getAccountsAtom = atom((_get) => {
  const { grantsProfile } = _get(getFormConfigAtom);

  return grantsProfile.bankAccounts;
});
export const getOfficialsAtom = atom((_get) => {
  const { grantsProfile } = _get(getFormConfigAtom);

  return grantsProfile.officials;
});
export const getSubmitStatusAtom = atom((_get) => {
  const { submitState } = _get(getFormConfigAtom);

  return submitState;
});
export const setSubmitStatusAtom = atom(
  null,
  (_get, _set, submitState: string) => {
    const formConfig = _get(getFormConfigAtom);

    _set(formConfigAtom, (state) => ({
      ...formConfig,
      submitState,
    }));
  },
);
export const getSchemasAtom = atom((_get) => {
  const { schema, uiSchema } = _get(getFormConfigAtom);

  return {
    schema,
    uiSchema,
  };
});
export const getApplicationNumberAtom = atom((_get) => {
  const { applicationNumber } = _get(getFormConfigAtom);

  return applicationNumber;
});
export const getTranslationsAtom = atom((_get) => {
  const { translations } = _get(getFormConfigAtom);

  return translations;
});
export const getSubventionFieldsAtom = atom((_get) => {
  const { subventionFields } = _get(getFormConfigAtom);

  return subventionFields;
});
export const getFormDataAtom = atom((_get) => {
  const formDataAtom = _get(formDataAtomRef);

  if (!formDataAtom) {
    throw new Error('Trying to read form data before initialization.');
  }

  return _get(formDataAtom);
});
export const setFormDataAtom = atom(null, (_get, _set, newData: any) => {
  const formDataAtom = _get(formDataAtomRef);

  if (!formDataAtom) {
    throw new Error('Trying to set form data before initialization.');
  }

  _set(formDataAtom, newData);
});
export const setApplicationNumberAtom = atom(
  null,
  (_get, _set, applicationNumber: string) => {
    const formConfig = _get(getFormConfigAtom);

    const currentParts = getUrlParts();
    currentParts[4] = applicationNumber;
    const currentUrl = new URL(window.location.href);
    currentUrl.pathname = currentParts.join('/');
    window.history.replaceState(null, '', currentUrl.toString());

    _set(formConfigAtom, (state) => ({
      ...formConfig,
      applicationNumber,
    }));
  },
);
export type SystemNotification = {
  children: string | ReactNode;
  label: string | ReactNode;
  type: 'info' | 'error' | 'alert' | 'success';
};
export const systemNotificationsAtom = atom<SystemNotification[]>([]);
export const pushNotificationAtom = atom(
  null,
  (_get, _set, notification: SystemNotification) => {
    _set(systemNotificationsAtom, (state) => [...state, notification]);
  },
);
export const shiftNotificationsAtom = atom(null, (_get, _set) => {
  _set(systemNotificationsAtom, (state) => state.slice(1));
});

type avus2Data = {
  attachmentsInfo: {
    attachmentsArray: Array<
      {
        ID: string;
        label: string;
        value: string;
        valueType: string;
      }[]
    >;
    generalInfoArray: Array<{
      ID: string;
      label: string;
      valueType: string;
    }>;
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
  }>;
  messages: any[];
  statusUpdates: Array<{
    caseId: string;
    citizenCaseStatus: string;
    eventType: string;
    eventCode: 1;
    eventSource: string;
    timeUpdated: string;
    timeCreated: string;
  }>;
};
export const avus2DataAtom = atom<avus2Data | null>();
export const shouldRenderPreviewAtom = atom((_get) => {
  const { currentStep } = _get(getFormStateAtom);

  return currentStep[1].id === 'preview';
});
export const isBeingSubmittedAtom = atom<boolean>(false);
export const isReadOnlyAtom = atom((_get) => {
  const { submitState } = _get(getFormConfigAtom);
  const isBeingSubmitted = _get(isBeingSubmittedAtom);

  return (
    submitState ===
      ![
        SubmitStates.DRAFT,
        SubmitStates.RECEIVED,
        SubmitStates.PREPARING,
      ].includes(submitState) || isBeingSubmitted
  );
});

export const getFormTitleAtom = atom((_get) => {
  const { settings } = _get(getFormConfigAtom);

  if (!settings) {
    return '';
  }

  return settings?.title ?? '';
});

import type { RJSFSchema, RJSFValidationError } from '@rjsf/utils';
import { atom } from 'jotai';
import type { ReactNode } from 'react';
import { SubmitStates } from './enum/SubmitStates';
import { getUrlParts } from './testutils/Helpers';
import type { Avus2Message } from './types/Avus2';
import type { FormConfig, ResponseData, SumMatchState } from './types/Data';
import type { RJSFFormData } from './types/RJSFFormData';
import { findFieldsOfType, findFieldsWithOption, getSumMatchRules, keyErrorsByStep } from './utils';

export type FormStep = { id: string; label: string };

export type FormState = {
  currentStep: [number, FormStep];
  validationCutoff: number;
};

const buildFormSteps = ({ properties }: RJSFSchema): Map<number, FormStep> => {
  const steps = new Map();

  if (!properties || typeof properties !== 'object') {
    console.error('Cannot build form steps, properties is not an object');
    return steps;
  }

  Object.entries(properties).forEach((property, index: number) => {
    const [key, value] = property;

    steps.set(index, {
      id: key,
      label: `${index + 1}. ${typeof value !== 'boolean' && value?.title ? value.title : ''}`,
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

const getFormStateKey = (applicationNumber: string) => `${applicationNumber}-form-state`;

/**
 * Discard the session copy of an application if the server copy is newer.
 *
 * @param {string} applicationNumber - The application number
 * @param {number} serverTimestamp - When the server copy was last changed, in seconds
 */
export const discardStaleSession = (applicationNumber: string, serverTimestamp?: number) => {
  const sessionItem = sessionStorage.getItem(applicationNumber);

  if (!sessionItem || !serverTimestamp) {
    return;
  }

  const { timestamp: sessionTimestamp } = JSON.parse(sessionItem);

  if (Number(sessionTimestamp) < Number(serverTimestamp)) {
    sessionStorage.removeItem(applicationNumber);
    sessionStorage.removeItem(getFormStateKey(applicationNumber));
  }
};

export const createFormDataAtom = (key: string, initialValue: RJSFFormData) => {
  initialValue = initialValue && Array.isArray(initialValue) && !initialValue.length ? {} : initialValue;

  const getInitialValue = () => {
    const sessionItem = sessionStorage.getItem(key);

    if (!sessionItem) {
      return initialValue;
    }

    return JSON.parse(sessionItem).data ?? {};
  };

  const baseAtom = atom(getInitialValue());
  const derivedAtom = atom(
    (get) => get(baseAtom),
    (get, set, update) => {
      const newValue = typeof update === 'function' ? update(get(baseAtom)) : update;
      set(baseAtom, newValue);
      sessionStorage.setItem(key, JSON.stringify({ timestamp: Math.floor(Date.now() / 1000), data: newValue }));
    },
  );

  return derivedAtom;
};

/**
 * Read the form state persisted for an application.
 *
 * @param {string} applicationNumber - The application number
 * @param {Map} steps - The form steps
 *
 * @return {FormState|undefined} - The persisted state, if it fits the steps
 */
const readPersistedFormState = (applicationNumber: string, steps: Map<number, FormStep>): FormState | undefined => {
  try {
    const { currentStep, validationCutoff } =
      JSON.parse(sessionStorage.getItem(getFormStateKey(applicationNumber)) ?? 'null') ?? {};
    const step = steps.get(currentStep);

    if (!step || step.id === 'ready' || !Number.isInteger(validationCutoff)) {
      return undefined;
    }

    return { currentStep: [currentStep, step], validationCutoff };
  } catch (_error) {
    return undefined;
  }
};

export const formDataAtomRef = atom<RJSFFormData | null>(null);
const baseFormStateAtom = atom<FormState | undefined>(undefined);

export const formStateAtom = atom(
  (get) => get(baseFormStateAtom),
  (get, set, update: FormState | undefined | ((state: FormState | undefined) => FormState | undefined)) => {
    const state = typeof update === 'function' ? update(get(baseFormStateAtom)) : update;
    set(baseFormStateAtom, state);

    const applicationNumber = get(formConfigAtom)?.applicationNumber;
    if (state && applicationNumber) {
      sessionStorage.setItem(
        getFormStateKey(applicationNumber),
        JSON.stringify({ currentStep: state.currentStep[0], validationCutoff: state.validationCutoff }),
      );
    }
  },
);
export const formConfigAtom = atom<FormConfig | undefined>(undefined);
export const formStepsAtom = atom<Map<number, FormStep> | undefined>(undefined);
export const errorsAtom = atom<Array<[number, RJSFValidationError]>>([]);
// Form data updated on every change. formDataAtom is only written after a
// debounce, so derived values like computed sums read this when available.
export const liveFormDataAtom = atom<RJSFFormData | null>(null);
// Results of the last validation of each sum match rule, keyed by rule path.
export const sumMatchStatesAtom = atom<Record<string, SumMatchState>>({});
export const setSumMatchStateAtom = atom(null, (_get, _set, path: string, state: SumMatchState | undefined) => {
  _set(sumMatchStatesAtom, (states) => {
    const { [path]: _previous, ...rest } = states;
    return state ? { ...rest, [path]: state } : rest;
  });
});
export const initializeFormAtom = atom(null, (_get, _set, formConfig: ResponseData) => {
  const {
    grants_profile: grantsProfile,
    status,
    summary_data: summaryData,
    ui_schema: uiSchema,
    schema,
    settings,
    ...rest
  } = formConfig;
  const { applicationNumber } = formConfig;
  const { acting_years: actingYears, applicant_type: applicantType } = settings || {};
  const steps = buildFormSteps(schema);
  _set(formStepsAtom, () => steps);
  _set(formConfigAtom, () => ({
    grantsProfile,
    ...rest,
    actingYears,
    applicantType,
    settings,
    requiredFileFields: Array.from(findFieldsWithOption(uiSchema, 'misc:required')),
    schema,
    submitState: status || SubmitStates.DRAFT,
    subventionFields: Array.from(findFieldsOfType(uiSchema, 'subventionTable')),
    sumMatchRules: getSumMatchRules(uiSchema),
    summaryData: summaryData
      ? {
          applicationNumber: summaryData?.application_number,
          applicationSubmitted: summaryData?.application_submitted,
          attachments: summaryData?.attachments,
          handlers: summaryData?.handlers,
          statusUpdates: summaryData?.status_updates,
        }
      : undefined,
    uiSchema,
  }));
  _set(formStateAtom, () => {
    const firstStep = steps.get(0);
    if (!firstStep) {
      console.error('Cannot initialize form state, no steps defined');
      return undefined;
    }

    return readPersistedFormState(applicationNumber, steps) ?? { currentStep: [0, firstStep], validationCutoff: -1 };
  });

  // Make sure application number is set in url params.
  _set(setApplicationNumberAtom, applicationNumber);
});
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
    throw new Error(`Index ${index} does not exist in defined steps for the form.`);
  }

  _set(formStateAtom, () => ({
    ...currentState,
    currentStep: [index, step],
  }));
  _set(finalAcceptanceAtom, false);
});
export const getValidationCutoffAtom = atom((_get) => {
  const { validationCutoff } = _get(getFormStateAtom);

  return validationCutoff;
});
export const raiseValidationCutoffAtom = atom(null, (_get, _set, index: number) => {
  const currentState = _get(getFormStateAtom);

  if (index <= currentState.validationCutoff) {
    return;
  }

  _set(formStateAtom, () => ({
    ...currentState,
    validationCutoff: index,
  }));
});
export const setErrorsAtom = atom(null, (_get, _set, errors: RJSFValidationError[], additiveOnly = false) => {
  const steps = _get(formStepsAtom);

  if (!additiveOnly) {
    _set(errorsAtom, () => keyErrorsByStep(errors, steps));
    return;
  }

  _set(errorsAtom, (state) => [...state, ...keyErrorsByStep(errors, steps)].sort(([a], [b]) => a - b));
});
export const getErrorPageIndicesAtom = atom((_get) => {
  const errors = _get(errorsAtom);

  return errors.map(([index]) => index);
});
export const getProfileAtom = atom((_get) => {
  const { grantsProfile } = _get(getFormConfigAtom);

  return grantsProfile;
});
export const getApplicantTypeAtom = atom((_get) => {
  const { grantsProfile } = _get(getFormConfigAtom);
  if (!grantsProfile.companyName) return 'private_person' as const;
  if (!grantsProfile.registrationDate) return 'unregistered_community' as const;
  return 'registered_community' as const;
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
export const setSubmitStatusAtom = atom(null, (_get, _set, submitState: string) => {
  const formConfig = _get(getFormConfigAtom);

  _set(formConfigAtom, () => ({ ...formConfig, submitState }));
});
export const getSchemasAtom = atom((_get) => {
  const { schema, uiSchema } = _get(getFormConfigAtom);

  return { schema, uiSchema };
});
export const getApplicationNumberAtom = atom((_get) => {
  const { applicationNumber } = _get(getFormConfigAtom);

  return applicationNumber;
});
export const getSummaryDataAtom = atom((_get) => {
  const formConfig = _get(getFormConfigAtom);

  return formConfig.summaryData;
});
export const getTranslationsAtom = atom((_get) => {
  const { translations } = _get(getFormConfigAtom);

  return translations;
});
export const getSubventionFieldsAtom = atom((_get) => {
  const { subventionFields } = _get(getFormConfigAtom);

  return subventionFields;
});
export const getRequiredFileFieldsAtom = atom((_get) => {
  const { requiredFileFields } = _get(getFormConfigAtom);

  return requiredFileFields;
});
export const getSumMatchRulesAtom = atom((_get) => {
  const { sumMatchRules } = _get(getFormConfigAtom);

  return sumMatchRules;
});
export const getFormDataAtom = atom((_get) => {
  const formDataAtom = _get(formDataAtomRef);

  if (!formDataAtom) {
    throw new Error('Trying to read form data before initialization.');
  }

  return _get(formDataAtom);
});
export const setFormDataAtom = atom(null, (_get, _set, newData: RJSFFormData) => {
  const formDataAtom = _get(formDataAtomRef);

  if (!formDataAtom) {
    throw new Error('Trying to set form data before initialization.');
  }

  _set(formDataAtom, newData);
});
export const setApplicationNumberAtom = atom(null, (_get, _set, applicationNumber: string) => {
  const formConfig = _get(getFormConfigAtom);

  if (!drupalSettings.grants_react_form.real_application_number) {
    const currentParts = getUrlParts();
    currentParts[4] = applicationNumber;
    const currentUrl = new URL(window.location.href);
    currentUrl.pathname = currentParts.join('/');
    window.history.replaceState(null, '', currentUrl.toString());
  }

  _set(formConfigAtom, () => ({ ...formConfig, applicationNumber }));
});
export type SystemNotification = {
  children: string | ReactNode;
  label: string | ReactNode;
  type: 'info' | 'error' | 'alert' | 'success';
};
export const systemNotificationsAtom = atom<SystemNotification[]>([]);
export const pushNotificationAtom = atom(null, (_get, _set, notification: SystemNotification) => {
  _set(systemNotificationsAtom, (state) => [...state, notification]);
});
export const shiftNotificationsAtom = atom(null, (_get, _set) => {
  _set(systemNotificationsAtom, (state) => state.slice(1));
});

type avus2Data = {
  attachmentsInfo: {
    attachmentsArray: Array<{ ID: string; label: string; value: string; valueType: string }[]>;
    generalInfoArray: Array<{ ID: string; label: string; valueType: string }>;
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
    timeCreated: string;
    timeUpdated: string;
  }>;
  messages: Avus2Message[];
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
export const avus2DataAtom = atom<avus2Data | null>(null);
export const shouldRenderPreviewAtom = atom((_get) => {
  const { currentStep } = _get(getFormStateAtom);

  return currentStep[1].id === 'preview';
});
export const isBeingSubmittedAtom = atom<boolean>(false);
export const isReadOnlyAtom = atom((_get) => {
  const { submitState } = _get(getFormConfigAtom);
  const isBeingSubmitted = _get(isBeingSubmittedAtom);

  // @todo Refactor: allow editing application if it has errors and locked
  // in submitted state.
  return (
    _get(isEmptyPreviewAtom) ||
    ![SubmitStates.DRAFT, SubmitStates.SUBMITTED, SubmitStates.RECEIVED, SubmitStates.PREPARING].includes(
      submitState,
    ) ||
    isBeingSubmitted
  );
});

export const getFormTitleAtom = atom((_get) => {
  const { settings } = _get(getFormConfigAtom);

  if (!settings) {
    return '';
  }

  return settings?.title ?? '';
});

export const isEmptyPreviewAtom = atom((_get) => {
  return drupalSettings.grants_react_form.use_empty_preview;
});
export const getActingYearsAtom = atom((_get) => {
  const { actingYears } = _get(getFormConfigAtom);

  return actingYears;
});

export const getUserTypeAtom = atom((_get) => {
  const { applicantType } = _get(getFormConfigAtom);

  return applicantType;
});

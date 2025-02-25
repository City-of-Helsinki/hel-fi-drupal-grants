import { atom } from 'jotai';
import { StepState } from 'hds-react';

export type FormStep = {
  id: string;
  label: string;
};

type FormState = {
  currentStep: [number, FormStep];
}

type FormConfig = {
  definitions: any;
  description: string;
  properties: any;
  title: string;
  translations: {
    [key in 'fi'|'sv'|'en']: {
      [key: string]: string;
    };
  };
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
export const initializeFormAtom = atom(null, (_get, _set, formConfig: FormConfig) => {
  const steps = buildFormSteps(formConfig);
  _set(formStepsAtom, state => steps);
  _set(formConfigAtom, (state) => formConfig);
  _set(formStateAtom, (state) => {
    return {
      currentStep: [0, steps.get(0)],
    };
  });
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

  _set(formStateAtom, state => {
    return state ? {
      ...state,
      currentStep: [index, step],
    } : state;
  });
});

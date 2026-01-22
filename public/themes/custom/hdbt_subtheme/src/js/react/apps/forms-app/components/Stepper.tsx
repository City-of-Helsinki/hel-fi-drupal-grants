import { useAtomValue, useSetAtom } from 'jotai';
import { Stepper as HDSStepper, StepState } from 'hds-react';
import { type MouseEvent, type RefObject, useEffect, useRef } from 'react';
import type Form from '@rjsf/core';
import {
  type FormStep,
  formStepsAtom,
  getCurrentStepAtom,
  getErrorPageIndicesAtom,
  getSubmitStatusAtom,
  setStepAtom,
} from '../store';
import { defaultStepperTheme } from '@/react/common/constants/stepperTheme';

export const transformSteps = (
  steps: Map<number, FormStep> | undefined,
  _submitState: string,
  errorIndices: number[] = [],
) => {
  if (!steps) {
    return [];
  }

  return Array.from(steps).map(([index, step]) => {
    const { label } = step;
    // biome-ignore lint/suspicious/noImplicitAnyLet: @todo UHF-12501
    let state;

    if (index === steps.size - 1) {
      state = StepState.disabled;
    } else {
      state = errorIndices.includes(index) ? StepState.attention : StepState.available;
    }

    return { label, state };
  });
};

export const Stepper = ({ formRef }: { formRef: RefObject<Form> }) => {
  const divRef = useRef<HTMLDivElement | null>(null);
  const [currentIndex] = useAtomValue(getCurrentStepAtom);
  const errorPageIndices = useAtomValue(getErrorPageIndicesAtom);
  const steps = useAtomValue(formStepsAtom);
  const setStep = useSetAtom(setStepAtom);
  const submitState = useAtomValue(getSubmitStatusAtom);
  const transformedSteps = transformSteps(steps, submitState, errorPageIndices);

  // biome-ignore lint/correctness/useExhaustiveDependencies: @todo UHF-12501
  useEffect(() => {
    if (!divRef?.current) {
      return;
    }
    divRef.current.scrollIntoView();

    const currentStep = divRef.current.querySelector('[aria-current="step"]');
    if (document.activeElement !== currentStep && currentStep) {
      (currentStep as HTMLElement).focus();
    }
  }, [divRef, currentIndex]);

  const onStepClick = (_event: MouseEvent<HTMLButtonElement>, stepIndex: number) => {
    formRef.current?.validateForm();
    setStep(stepIndex);
  };

  return (
    <div ref={divRef}>
      <HDSStepper
        className='hdbt-form--stepper'
        language={drupalSettings.path.currentLanguage}
        onStepClick={onStepClick}
        selectedStep={currentIndex}
        steps={transformedSteps}
        theme={defaultStepperTheme}
      />
    </div>
  );
};

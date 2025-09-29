import { useAtomValue, useSetAtom } from 'jotai';
import { Stepper as HDSStepper, StepState } from 'hds-react';
import React, { MouseEvent, RefObject, useEffect, useRef } from 'react';
import Form from '@rjsf/core';
import { FormStep, formStepsAtom, getCurrentStepAtom, getErrorPageIndicesAtom, getSubmitStatusAtom, setStepAtom } from '../store';
import { SubmitStates } from '../enum/SubmitStates';

export const transformSteps = (
  steps: Map<number, FormStep>|undefined,
  submitState: string,
  errorIndices: number[] = [],
) => {
  if (!steps) {
    return [];
  }

  return Array.from(steps).map(([index, step]) => {
    const { label } = step;
    let state;

    if (index === steps.size - 1) {
      state = submitState === SubmitStates.DRAFT ? StepState.disabled : StepState.available;
    }
    else {
      state = errorIndices.includes(index) ? StepState.attention : StepState.available;
    }

    return {
      label,
      state,
    };
  });
}

export const Stepper = ({
  formRef,
}: {
  formRef: RefObject<Form>
}) => {
  const divRef = useRef<HTMLDivElement|null>(null);
  const [currentIndex] = useAtomValue(getCurrentStepAtom);
  const errorPageIndices = useAtomValue(getErrorPageIndicesAtom);
  const steps = useAtomValue(formStepsAtom);
  const setStep = useSetAtom(setStepAtom);
  const submitState = useAtomValue(getSubmitStatusAtom);
  const transformedSteps = transformSteps(steps, submitState, errorPageIndices);

  useEffect(() => {
    if (divRef?.current?.scrollIntoView) {
      divRef.current.scrollIntoView();
    }
  }, [divRef, currentIndex])

  const onStepClick = (event: MouseEvent<HTMLButtonElement>, stepIndex: number) => {
    formRef.current?.validateForm();
    setStep(stepIndex);
  }

  return (
    <div ref={divRef}>
      <HDSStepper
        language={drupalSettings.path.currentLanguage}
        onStepClick={onStepClick}
        selectedStep={currentIndex}
        steps={transformedSteps}
        theme={{
          '--hds-not-selected-step-label-color': 'var(--color-black-90)',
          '--hds-step-content-color': 'var(--color-black-90)',
          '--hds-stepper-background-color': 'var(--color-white)',
          '--hds-stepper-color': 'var(--color-black-90)',
          '--hds-stepper-focus-border-color': 'var(--color-coat-of-arms)'
        }}
        className='hdbt-form--stepper'
      />
    </div>
  )
};

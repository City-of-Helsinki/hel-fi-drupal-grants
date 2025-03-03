import { useAtomValue, useSetAtom } from 'jotai';
import { Stepper as HDSStepper, StepState } from 'hds-react';
import React, { MouseEvent, RefObject, useEffect, useRef } from 'react';
import Form from '@rjsf/core';
import { FormStep, formStepsAtom, getCurrentStepAtom, getErrorPageIndicesAtom, setStepAtom } from '../store';

const transformSteps = (
  steps: Map<number, FormStep>|undefined,
  errorIndices: number[] = [],
) => {
  if (!steps) {
    return [];
  }

  return Array.from(steps).map(([index, step]) => ({
    label: step.label,
    state: errorIndices.includes(index) ? StepState.attention : StepState.available,
  }));
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
  const transformedSteps = transformSteps(steps, errorPageIndices);

  useEffect(() => {
    if (divRef.current) {
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
          '--hds-step-content-color': 'var(--color-black)',
          '--hds-stepper-color': 'var(--color-black)',
          '--hds-stepper-focus-border-color': 'var(--color-black)',
          '--hds-not-selected-step-label-color': 'var(--color-black)',
        }}
      />
    </div>
  )
};

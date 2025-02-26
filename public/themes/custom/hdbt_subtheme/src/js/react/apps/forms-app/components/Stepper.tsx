import { useAtomValue, useSetAtom } from 'jotai';
import { Stepper as HDSStepper, StepState } from 'hds-react';
import { RefObject, useEffect, useRef } from 'react';
import { FormStep, formStepsAtom, getCurrentStepAtom, setStepAtom } from '../store';
import { RJSFValidationError } from '@rjsf/utils';
import Form from '@rjsf/core';

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

export const Stepper = ({
  formRef,
}: {
  formRef: RefObject<Form>
}) => {
  const divRef = useRef<HTMLDivElement|null>(null);
  const [currentIndex] = useAtomValue(getCurrentStepAtom);
  const steps = useAtomValue(formStepsAtom);
  const setStep = useSetAtom(setStepAtom);
  const errors = formRef.current?.state.errors;
  const transformedSteps = transformSteps(steps, getIndicesWithErrors(errors, steps));

  useEffect(() => {
    if (divRef.current) {
      divRef.current.scrollIntoView();
    }
  }, [divRef, currentIndex])

  return (
    <div ref={divRef}>
      <HDSStepper
        className='grants-stepper'
        language={drupalSettings.path.currentLanguage}
        steps={transformedSteps}
        onStepClick={(event, stepIndex) => setStep(stepIndex)}
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

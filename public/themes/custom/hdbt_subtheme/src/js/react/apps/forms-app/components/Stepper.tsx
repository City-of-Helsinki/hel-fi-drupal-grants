import { useAtomValue, useSetAtom } from 'jotai';
import { Stepper as HDSStepper, StepState } from 'hds-react';
import { useEffect, useRef } from 'react';
import { FormStep, formStepsAtom, getCurrentStepAtom, setStepAtom } from '../store';

const transformSteps = (steps: Map<number, FormStep>|undefined) => {
  if (!steps) {
    return [];
  }

  return Array.from(steps).map(([index, step]) => ({
    label: step.label,
    state: StepState.available,
  }));
}

export const Stepper = () => {
  const divRef = useRef<HTMLDivElement|null>(null);
  const [currentIndex] = useAtomValue(getCurrentStepAtom);
  const steps = useAtomValue(formStepsAtom);
  const setStep = useSetAtom(setStepAtom);
  const transformedSteps = transformSteps(steps);

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

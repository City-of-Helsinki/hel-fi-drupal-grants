import { describe, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { TestProvider } from '../../testutils/TestProvider';
import { formStateAtom, formStepsAtom } from '../../store';
import { initializeFormState } from '../../testutils/Helpers';
import { testKeyedErrors, testSteps } from '../../testutils/Data';
import { Stepper, transformSteps } from '../../components/Stepper';
import { StepState } from 'hds-react';

describe('Stepper.tsx tests', () => {
  render(
    <TestProvider initialValues={[
      [formStateAtom, initializeFormState({
        errors: testKeyedErrors,
        reachedStep: 1,
      })],
      [formStepsAtom, testSteps],
    ]}>
      <Stepper formRef={null} />
    </TestProvider>
  );

  it('Renders stepper', () => {
    expect(screen.getByText(testSteps.get(0)?.label)).toBeTruthy();
    expect(screen.getByText(testSteps.get(1)?.label)).toBeTruthy();
  })

  it('Transforms steps correctly', () => {
    expect(transformSteps(undefined, [])).toEqual([]);
    expect(transformSteps(testSteps, [])).toEqual([
      {
        label: testSteps.get(0)?.label,
        state: StepState.available,
      },
      {
        label: testSteps.get(1)?.label,
        state: StepState.available,
      }
    ]);
    expect(transformSteps(testSteps, [0])).toEqual([
      {
        label: testSteps.get(0)?.label,
        state: StepState.attention,
      },
      {
        label: testSteps.get(1)?.label,
        state: StepState.available,
      }
    ])
  });
});

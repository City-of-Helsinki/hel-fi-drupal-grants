import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { StepState } from 'hds-react';
import { TestProvider } from '../../testutils/TestProvider';
import { formStateAtom, formStepsAtom } from '../../store';
import { initializeFormState } from '../../testutils/Helpers';
import { testKeyedErrors, testSteps } from '../../testutils/Data';
import { Stepper, transformSteps } from '../../components/Stepper';

describe('Stepper.tsx tests', () => {
  render(
    <TestProvider initialValues={[
      [formStateAtom, initializeFormState({
        errors: testKeyedErrors,
        reachedStep: 1,
      })],
      [formStepsAtom, testSteps],
    ]}>
      {/* @ts-ignore */}
      <Stepper formRef={null} />
    </TestProvider>
  );

  it('Renders stepper', () => {
    expect(screen.getByText('Step 1')).toBeTruthy();
    expect(screen.getByText('Step 2')).toBeTruthy();
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

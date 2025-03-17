import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { StepState } from 'hds-react';
import { TestProvider } from '../../testutils/TestProvider';
import { formConfigAtom, formStateAtom, formStepsAtom } from '../../store';
import { initializeFormState } from '../../testutils/Helpers';
import { testKeyedErrors, testSteps } from '../../testutils/Data';
import { Stepper, transformSteps } from '../../components/Stepper';
import { SubmitStates } from '../../enum/SubmitStates';

describe('Stepper.tsx tests', () => {
  render(
    <TestProvider initialValues={[
      [formStateAtom, initializeFormState({
        errors: testKeyedErrors,
        reachedStep: 1,
      })],
      [formStepsAtom, testSteps],
      [formConfigAtom, {
        submitState: SubmitStates.unsubmitted,
      }]
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
    expect(transformSteps(undefined, SubmitStates.unsubmitted, [])).toEqual([]);
    expect(transformSteps(testSteps, SubmitStates.unsubmitted, [])).toEqual([
      {
        label: testSteps.get(0)?.label,
        state: StepState.available,
      },
      {
        label: testSteps.get(1)?.label,
        state: StepState.disabled,
      }
    ]);
    expect(transformSteps(testSteps, SubmitStates.unsubmitted, [0])).toEqual([
      {
        label: testSteps.get(0)?.label,
        state: StepState.attention,
      },
      {
        label: testSteps.get(1)?.label,
        state: StepState.disabled,
      }
    ])
  });
});

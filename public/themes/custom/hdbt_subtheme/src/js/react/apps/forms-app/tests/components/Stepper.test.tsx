import { fireEvent, render, screen } from '@testing-library/react';
import { StepState } from 'hds-react';
import { createStore, Provider } from 'jotai';
import { describe, expect, it, vi } from 'vitest';
import { Stepper, transformSteps } from '../../components/Stepper';
import { SubmitStates } from '../../enum/SubmitStates';
import { formConfigAtom, formStateAtom, formStepsAtom, getReachedStepAtom } from '../../store';
import { testSteps } from '../../testutils/Data';
import { initializeFormState } from '../../testutils/Helpers';
import { TestProvider } from '../../testutils/TestProvider';
import type { FormConfig } from '../../types/Data';

describe('Stepper.tsx tests', () => {
  render(
    <TestProvider
      initialValues={[
        [formStateAtom, initializeFormState({ reachedStep: 1 })],
        [formStepsAtom, testSteps],
        [formConfigAtom, { submitState: SubmitStates.DRAFT }],
      ]}
    >
      {/* @ts-ignore */}
      <Stepper formRef={null} />
    </TestProvider>,
  );

  it('Renders stepper', () => {
    expect(screen.getByText('Step 1')).toBeTruthy();
    expect(screen.getByText('Step 2')).toBeTruthy();
  });

  it('Transforms steps correctly', () => {
    expect(transformSteps(undefined, SubmitStates.DRAFT, [])).toEqual([]);
    expect(transformSteps(testSteps, SubmitStates.DRAFT, [])).toEqual([
      { label: testSteps.get(0)?.label, state: StepState.available },
      { label: testSteps.get(1)?.label, state: StepState.disabled },
    ]);
    expect(transformSteps(testSteps, SubmitStates.DRAFT, [0])).toEqual([
      { label: testSteps.get(0)?.label, state: StepState.attention },
      { label: testSteps.get(1)?.label, state: StepState.disabled },
    ]);
  });

  it('Updates reached step before validating on step click', () => {
    const store = createStore();
    store.set(formStateAtom, initializeFormState({ reachedStep: 0 }));
    store.set(
      formStepsAtom,
      new Map([
        [0, { id: 'step-1', label: 'Step 1' }],
        [1, { id: 'step-2', label: 'Step 2' }],
        [2, { id: 'preview', label: 'Preview' }],
        [3, { id: 'ready', label: 'Ready' }],
      ]),
    );
    store.set(formConfigAtom, { submitState: SubmitStates.DRAFT } as FormConfig);

    let reachedStepOnValidate: number | undefined;
    const validateForm = vi.fn(() => {
      reachedStepOnValidate = store.get(getReachedStepAtom);
      return true;
    });

    render(
      <Provider store={store}>
        {/* @ts-ignore */}
        <Stepper formRef={{ current: { validateForm } }} />
      </Provider>,
    );

    fireEvent.click(screen.getByText('Preview'));

    expect(validateForm).toHaveBeenCalledOnce();
    expect(reachedStepOnValidate).toBe(2);
  });
});

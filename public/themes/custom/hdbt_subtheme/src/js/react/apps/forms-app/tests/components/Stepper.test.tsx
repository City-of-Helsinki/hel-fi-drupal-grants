import { fireEvent, render, screen } from '@testing-library/react';
import { StepState } from 'hds-react';
import { createStore, Provider } from 'jotai';
import { describe, expect, it, vi } from 'vitest';
import { Stepper, transformSteps } from '../../components/Stepper';
import { SubmitStates } from '../../enum/SubmitStates';
import { formConfigAtom, formStateAtom, formStepsAtom, getValidationCutoffAtom } from '../../store';
import { testSteps } from '../../testutils/Data';
import { initializeFormState } from '../../testutils/Helpers';
import { TestProvider } from '../../testutils/TestProvider';
import type { FormConfig } from '../../types/Data';

describe('Stepper.tsx tests', () => {
  render(
    <TestProvider
      initialValues={[
        [formStateAtom, initializeFormState({ validationCutoff: 1 })],
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

  const renderStepper = (validationCutoff: number) => {
    const store = createStore();
    store.set(formStateAtom, initializeFormState({ validationCutoff }));
    store.set(
      formStepsAtom,
      new Map([
        [0, { id: 'step-1', label: 'Step 1' }],
        [1, { id: 'step-2', label: 'Step 2' }],
        [2, { id: 'step-3', label: 'Step 3' }],
        [3, { id: 'preview', label: 'Preview' }],
        [4, { id: 'ready', label: 'Ready' }],
      ]),
    );
    store.set(formConfigAtom, { submitState: SubmitStates.DRAFT } as FormConfig);

    let cutoffOnValidate: number | undefined;
    const validateForm = vi.fn(() => {
      cutoffOnValidate = store.get(getValidationCutoffAtom);
      return true;
    });

    render(
      <Provider store={store}>
        {/* @ts-ignore */}
        <Stepper formRef={{ current: { validateForm } }} />
      </Provider>,
    );

    return { getCutoffOnValidate: () => cutoffOnValidate, validateForm };
  };

  it('Validates up to the page before the clicked one on step click', () => {
    const { getCutoffOnValidate, validateForm } = renderStepper(0);

    fireEvent.click(screen.getAllByText('Step 3')[0]);

    expect(validateForm).toHaveBeenCalledOnce();
    expect(getCutoffOnValidate()).toBe(1);
  });

  it('Keeps validating pages validated before on step click', () => {
    const { getCutoffOnValidate } = renderStepper(2);

    fireEvent.click(screen.getAllByText('Step 2')[0]);

    expect(getCutoffOnValidate()).toBe(2);
  });
});

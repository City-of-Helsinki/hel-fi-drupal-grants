import { fireEvent, render, screen } from '@testing-library/react';
import { createStore, Provider } from 'jotai';
import { describe, expect, it, vi } from 'vitest';
import { FormActions } from '../../components/FormActions/FormActions';
import { SubmitStates } from '../../enum/SubmitStates';
import { errorsAtom, formConfigAtom, formStateAtom, formStepsAtom, getValidationCutoffAtom } from '../../store';
import { testKeyedErrors, testSteps } from '../../testutils/Data';
import { initializeFormState } from '../../testutils/Helpers';
import { TestProvider } from '../../testutils/TestProvider';
import type { FormConfig } from '../../types/Data';

describe('FormActions.tsx tests', () => {
  render(
    <TestProvider
      initialValues={[
        [errorsAtom, testKeyedErrors],
        [
          formStateAtom,
          initializeFormState({ currentStep: [0, { id: 'step-1', label: 'Step 1' }], validationCutoff: 0 }),
        ],
        [formStepsAtom, testSteps],
        [formConfigAtom, { submitState: SubmitStates.DRAFT }],
      ]}
    >
      <FormActions saveDraft={() => Promise.resolve()} validatePartialForm={() => undefined} />
    </TestProvider>,
  );

  it('Renders all buttons', () => {
    // This button is disabled for now
    // expect(screen.getByText('Delete draft')).toBeTruthy();
    expect(screen.getByText('Save as draft')).toBeTruthy();
    expect(screen.getByText('Next')).toBeTruthy();
  });

  it('Validates up to the current page on next', () => {
    const store = createStore();
    store.set(
      formStateAtom,
      initializeFormState({ currentStep: [1, { id: 'step-2', label: 'Step 2' }], validationCutoff: 0 }),
    );
    store.set(
      formStepsAtom,
      new Map([
        [0, { id: 'step-1', label: 'Step 1' }],
        [1, { id: 'step-2', label: 'Step 2' }],
        [2, { id: 'step-3', label: 'Step 3' }],
      ]),
    );
    store.set(formConfigAtom, { submitState: SubmitStates.DRAFT } as FormConfig);

    let cutoffOnValidate: number | undefined;
    const validatePartialForm = vi.fn(() => {
      cutoffOnValidate = store.get(getValidationCutoffAtom);
      return undefined;
    });

    render(
      <Provider store={store}>
        <FormActions saveDraft={() => Promise.resolve()} validatePartialForm={validatePartialForm} />
      </Provider>,
    );
    fireEvent.click(screen.getAllByText('Next').at(-1) as HTMLElement);

    expect(cutoffOnValidate).toBe(1);
    expect(store.get(formStateAtom)?.currentStep[0]).toBe(2);
  });
});

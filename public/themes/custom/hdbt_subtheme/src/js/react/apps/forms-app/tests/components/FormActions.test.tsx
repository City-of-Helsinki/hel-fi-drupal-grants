import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { FormActions } from '../../components/FormActions/FormActions';
import { SubmitStates } from '../../enum/SubmitStates';
import { errorsAtom, formConfigAtom, formStateAtom, formStepsAtom } from '../../store';
import { testKeyedErrors, testSteps } from '../../testutils/Data';
import { initializeFormState } from '../../testutils/Helpers';
import { TestProvider } from '../../testutils/TestProvider';

describe('FormActions.tsx tests', () => {
  render(
    <TestProvider
      initialValues={[
        [errorsAtom, testKeyedErrors],
        [formStateAtom, initializeFormState({ currentStep: [0, { id: 'step-1', label: 'Step 1' }], reachedStep: 0 })],
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
});

import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { TestProvider } from '../../testutils/TestProvider';
import { FormActions } from '../../components/FormActions/FormActions';
import { errorsAtom, formStateAtom, formStepsAtom } from '../../store';
import { initializeFormState } from '../../testutils/Helpers';
import { testKeyedErrors, testSteps } from '../../testutils/Data';

describe('FormActions.tsx tests', () => {
  render(
    <TestProvider
      initialValues={[
        [errorsAtom, testKeyedErrors],
        [formStateAtom, initializeFormState({ currentStep: [0, { id: 'step-1', label: 'Step 1' }], reachedStep: 0 })],
        [formStepsAtom, testSteps],
      ]}
    >
      <FormActions
        saveDraft={() => {
          const result: Promise<boolean> = new Promise((resolve) => {
            resolve(true);
          });

          return result;
        }}
        validatePartialForm={() => undefined}
      />
    </TestProvider>,
  );

  it('Renders all buttons', () => {
    // This button is disabled for now
    // expect(screen.getByText('Delete draft')).toBeTruthy();
    expect(screen.getByText('Save as draft')).toBeTruthy();
    expect(screen.getByText('Next')).toBeTruthy();
  });
});

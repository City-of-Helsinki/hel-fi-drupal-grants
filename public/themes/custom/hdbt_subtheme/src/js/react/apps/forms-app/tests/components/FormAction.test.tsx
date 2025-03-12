import { describe, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { TestProvider } from '../../testutils/TestProvider';
import { FormActions } from '../../components/FormActions';
import { formStateAtom, formStepsAtom } from '../../store';
import { initializeFormState } from '../../testutils/Helpers';
import { testKeyedErrors, testSteps } from '../../testutils/Data';

describe('FormAction.tsx tests', () => {
  render(
    <TestProvider initialValues={[
      [formStateAtom, initializeFormState({
        currentStep: [0, {id: 'step-1', label: 'Step 1'}],
        errors: testKeyedErrors,
        reachedStep: 0,
      })],
      [formStepsAtom, testSteps]
    ]}>
      <FormActions />
    </TestProvider>
  );

  it('Renders all buttons', () => {
    expect(screen.getByText('Delete draft')).toBeTruthy();
    expect(screen.getByText('Save as draft')).toBeTruthy();
    expect(screen.getByText('Next')).toBeTruthy();
  });
});

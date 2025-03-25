import { FormState, FormStep } from '../store';

const initialStep: [number, FormStep] = [0, {label: 'Step 1', id: 'step-1'}];

const initialState = {
  currentStep: initialStep,
  errors: [],
  reachedStep: 0,
};

/**
 * Returns valid state from partial data.
 *
 * @param {Object} formState - Partially filled state
 * @return {Object} - Resulting valid state.
 */
export const initializeFormState = (formState: Partial<FormState>): FormState => ({
    ...initialState,
    ...formState
  });

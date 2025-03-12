import { FormState } from 'store';
import { C } from 'vitest/dist/chunks/reporters.66aFHiyX';

const initialState = {
  currentStep: [0, {}],
  errors: [],
  reachedStep: 0,
};

/**
 * Returns valid state from partial data.
 *
 * @param {object} formState
 * @returns {object} - Resulting valid state.
 */
export const initializeFormState = (formState: FormState) => {
  return {
    ...initialState,
    ...formState
  };
};

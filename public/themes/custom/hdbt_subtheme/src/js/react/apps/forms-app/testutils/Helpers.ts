import type { FormState, FormStep } from '../store';

const initialStep: [number, FormStep] = [0, { label: 'Step 1', id: 'step-1' }];

const initialState = { currentStep: initialStep, data: {}, errors: [], reachedStep: 0 };

/**
 * Returns valid state from partial data.
 *
 * @param {Object} formState - Partially filled state
 * @return {Object} - Resulting valid state.
 */
export const initializeFormState = (formState: Partial<FormState>): FormState => ({ ...initialState, ...formState });

/**
 * Get URL path parts from current URL in an array.
 *
 * @return {string[]} - Array of URL parts.
 */
export const getUrlParts = () => {
  const path = window.location.pathname;

  return path.split('/').filter(Boolean);
};

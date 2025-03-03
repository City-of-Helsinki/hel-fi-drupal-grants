import { RJSFValidationError } from '@rjsf/utils';
import { FormStep } from './store';

const regex = '/^.([^.]+)/';

/**
 * Return index numbers for steps that have errors in them.
 *
 * @param {Array|undefined} errors - array of RJSValidationErrors
 * @param {Map} steps - Steps from form config
 *
 * @return {Array} - Array of step indices with errors in them
 */
export const getIndicesWithErrors = (
  errors: RJSFValidationError[]|undefined,
  steps?: Map<number, FormStep>,
) => {
  if (!steps || !errors || !errors?.length) {
    return [];
  }

  const errorIndices: number[] = [];
  const propertyParentKeys: string[] = [];
  errors.forEach(error => {
    const match = error?.property?.match(regex)?.[0];

    if (match) {
      propertyParentKeys.push(match.split('.')[1]);
    }
  });
  Array.from(steps).forEach(([index, step]) => {
    if (propertyParentKeys.includes(step.id)) {
      errorIndices.push(index);
    }
  });

  return errorIndices;
};

/**
 * Key errors by page index and return them unaltered.
 *
 * @param {Array|undefined} errors - array of RJSValidationErrors
 * @param {Map} steps - Steps from form config
 *
 * @return {Array} - Array of validation errors, keyed by step index
 */
export const keyErrorsByStep = (
  errors: RJSFValidationError[]|undefined,
  steps?: Map<number, FormStep>,
) => {
  if (!steps || !errors || !errors?.length) {
    return [];
  }

  const keyedErrors: Array<[number, RJSFValidationError]> = [];

  errors.forEach(error => {
    const match = error?.property?.match(regex)?.[0];

    const matchedStep = Array.from(steps).find(([index, step]) => step.id === match?.split('.')[1]);

    if (matchedStep) {
      const [matchedIndex] = matchedStep;
      keyedErrors.push([matchedIndex, error]);
    }
  });

  return keyedErrors;
};

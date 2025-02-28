import { RJSFValidationError } from '@rjsf/utils';
import { FormStep } from './store';

const regex = new RegExp('^\.([^\.]+)');

/**
 * Return index numbers for steps that have errors in them.
 *
 * @param RJSFValidationError[]|undefined errors
 * @param Map<number, FormStep> steps
 * @returns Array<number>
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
    let match = error?.property?.match(regex)?.[0];

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
 * @param RJSFValidationError[]|undefined errors
 * @param Map<number, FormStep> steps
 * @returns Array<Array<number, RJSFValidationError>>
 */
export const keyErrorsByStep = (
  errors: RJSFValidationError[]|undefined,
  steps?: Map<number, FormStep>,
) => {
  if (!steps || !errors || !errors?.length) {
    return [];
  }

  const keyedErrors: Array<[number, RJSFValidationError]> = [];

  for (const error of errors) {
    const match = error?.property?.match(regex)?.[0];

    if (!match) {
      continue;
    }

    const matchedStep = Array.from(steps).find(([index, step]) => step.id === match.split('.')[1]);

    if (matchedStep) {
      const [matchedIndex] = matchedStep;
      keyedErrors.push([matchedIndex, error]);
    }
  };

  return keyedErrors;
};

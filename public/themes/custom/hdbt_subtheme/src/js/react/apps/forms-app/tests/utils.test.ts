import { describe, expect, test } from 'vitest';
import { addApplicantInfoStep, getIndicesWithErrors, isValidFormResponse, keyErrorsByStep } from '../utils';
import { testErrors, testKeyedErrors, testSteps } from '../testutils/Data';
import { communitySettings } from '../formConstants';

describe('Utils.ts', () => {
  test('getIndicesWithErrors', () => {
    expect(getIndicesWithErrors(undefined)).toEqual([]);
    expect(getIndicesWithErrors([])).toEqual([]);
    expect(getIndicesWithErrors(testErrors)).toEqual([]);
    expect(getIndicesWithErrors(testErrors, testSteps)).toEqual([0, 1]);
  });

  test('keyErrorsByStep', () => {
    expect(keyErrorsByStep(undefined)).toEqual([]);
    expect(keyErrorsByStep(testErrors, testSteps)).toEqual(testKeyedErrors);
  });

  // @todo implement actual test once this function is actually implemented
  test('isValidFormResponse', () => {
    expect(isValidFormResponse({})).toEqual([true, undefined]);
  });

  test('addApplicantInfoStep', () => {
    const [rootProperty, definition, uiSchemaAdditions] = communitySettings;
    const result = addApplicantInfoStep({}, {}, {business_id: '123'});

    expect(result[0]).toEqual({
      definitions: {
        applicant_info: definition,
      },
      properties: {
        applicant_info: rootProperty,
      },
    });

    expect(result[1]).toEqual({
      ...uiSchemaAdditions,
    });
  });
});

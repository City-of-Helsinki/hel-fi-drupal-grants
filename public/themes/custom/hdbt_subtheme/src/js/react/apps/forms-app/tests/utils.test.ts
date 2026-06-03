import { describe, expect, test } from 'vitest';
import { UserType } from '../enum/UserType';
import { communitySettings } from '../formConstants';
import { testErrors, testKeyedErrors, testSteps } from '../testutils/Data';
import { addApplicantInfoStep, getIndicesWithErrors, getSubventionSum, keyErrorsByStep } from '../utils';

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

  describe('getSubventionSum', () => {
    test('returns "0" when no subvention fields are provided', () => {
      expect(getSubventionSum({}, [])).toBe('0');
    });

    test('returns "0" when form data has no matching fields', () => {
      expect(getSubventionSum({}, ['.step1.subventionTable'])).toBe('0');
    });

    test('returns "0" when matching field has no entries', () => {
      expect(getSubventionSum({ step1: { subventionTable: [] } }, ['.step1.subventionTable'])).toBe('0');
    });

    test('sums integer values from a single table', () => {
      const formData = {
        step1: {
          subventionTable: [
            ['key1', { value: '100' }],
            ['key2', { value: '200' }],
          ],
        },
      };
      expect(getSubventionSum(formData, ['.step1.subventionTable'])).toBe('300');
    });

    test('sums decimal values using comma as separator', () => {
      const formData = {
        step1: {
          subventionTable: [
            ['key1', { value: '100,50' }],
            ['key2', { value: '50,25' }],
          ],
        },
      };
      expect(getSubventionSum(formData, ['.step1.subventionTable'])).toBe('150,75');
    });

    test('aggregates values across multiple fields', () => {
      const formData = {
        step1: {
          tableA: [['key1', { value: '100' }]],
          tableB: [['key2', { value: '250' }]],
        },
      };
      expect(getSubventionSum(formData, ['.step1.tableA', '.step1.tableB'])).toBe('350');
    });

    test('ignores entries where value is not a number', () => {
      const formData = {
        step1: {
          subventionTable: [
            ['key1', { value: '200' }],
            ['key2', { value: 'not-a-number' }],
          ],
        },
      };
      expect(getSubventionSum(formData, ['.step1.subventionTable'])).toBe('200');
    });

    test('handles a single entry', () => {
      const formData = {
        step1: { subventionTable: [['key1', { value: '42' }]] },
      };
      expect(getSubventionSum(formData, ['.step1.subventionTable'])).toBe('42');
    });

    test('returns an integer result without trailing comma when sum is whole', () => {
      const formData = {
        step1: {
          subventionTable: [
            ['key1', { value: '1,50' }],
            ['key2', { value: '0,50' }],
          ],
        },
      };
      expect(getSubventionSum(formData, ['.step1.subventionTable'])).toBe('2');
    });

    test('handles zero values', () => {
      const formData = {
        step1: { subventionTable: [['key1', { value: '0' }]] },
      };
      expect(getSubventionSum(formData, ['.step1.subventionTable'])).toBe('0');
    });
  });

  test('addApplicantInfoStep', () => {
    const [rootProperty, definition, uiSchemaAdditions] = communitySettings;

    const result = addApplicantInfoStep({}, {}, UserType.REGISTERED_COMMUNITY);

    expect(result[0]).toEqual({
      definitions: { applicant_info: definition },
      properties: { applicant_info: rootProperty },
    });

    expect(result[1]).toEqual({ ...uiSchemaAdditions });
  });
});

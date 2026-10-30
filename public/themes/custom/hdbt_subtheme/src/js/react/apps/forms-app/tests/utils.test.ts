import { describe, expect, test } from 'vitest';
import { UserType } from '../enum/UserType';
import { communitySettings } from '../formConstants';
import { testErrors, testKeyedErrors, testSteps } from '../testutils/Data';
import {
  addApplicantInfoStep,
  findSumMatchRule,
  getIndicesWithErrors,
  getSubventionSum,
  getSumMatchRules,
  getSumMatchState,
  getSumMismatch,
  hasSumMatchValuesOnBothSides,
  keyErrorsByStep,
  sumFieldValues,
} from '../utils';

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

    test('sums only rows matching the given subventionType', () => {
      const formData = {
        step1: {
          subventionTable: [
            [
              { ID: 'subventionType', value: '1' },
              { ID: 'amount', value: '5000' },
            ],
            [
              { ID: 'subventionType', value: '2' },
              { ID: 'amount', value: '1200' },
            ],
          ],
        },
      };
      expect(getSubventionSum(formData, ['.step1.subventionTable'], '2')).toBe('1200');
      expect(getSubventionSum(formData, ['.step1.subventionTable'], '1')).toBe('5000');
    });

    test('returns "0" when no rows match the given subventionType', () => {
      const formData = {
        step1: {
          subventionTable: [
            [
              { ID: 'subventionType', value: '1' },
              { ID: 'amount', value: '5000' },
            ],
          ],
        },
      };
      expect(getSubventionSum(formData, ['.step1.subventionTable'], '2')).toBe('0');
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

describe('sum match', () => {
  const formData = {
    step: {
      ages: { minors: 3, adults: '5', seniors: '' },
      genders: { women: 4, men: 3, other: '0,5', unknown: '0,5' },
    },
  };
  const rule = {
    path: 'step.genders.total',
    fields: ['step.genders.women', 'step.genders.men', 'step.genders.other', 'step.genders.unknown'],
    compareTo: ['step.ages.minors', 'step.ages.adults', 'step.ages.seniors'],
  };

  test('sumFieldValues sums numbers and comma decimals, ignoring empty and missing values', () => {
    expect(sumFieldValues(formData, rule.fields)).toBe(8);
    expect(sumFieldValues(formData, [...rule.compareTo, 'step.ages.missing'])).toBe(8);
    expect(sumFieldValues(formData, [])).toBe(0);
  });

  test('getSumMismatch passes when sums are equal', () => {
    expect(getSumMismatch(formData, rule)).toBeNull();
  });

  test('getSumMismatch returns both sums when they differ', () => {
    const data = { step: { ...formData.step, ages: { minors: 1, adults: 1, seniors: 1 } } };
    expect(getSumMismatch(data, rule)).toEqual([8, 3]);
  });

  test('getSumMismatch passes when all fields are empty', () => {
    expect(getSumMismatch({ step: { ages: { minors: '' } } }, rule)).toBeNull();
  });

  test('getSumMismatch fails when only one side is filled', () => {
    expect(getSumMismatch({ step: { ages: { minors: 2 } } }, rule)).toEqual([0, 2]);
  });

  test('getSumMatchRules reads rules and resolves default fields', () => {
    const uiSchema = {
      step: {
        genders: {
          total: {
            'ui:field': 'computedSum',
            'ui:options': { sourceFields: rule.fields },
            'misc:sum-match': { compareTo: rule.compareTo, message: 'custom.key' },
          },
        },
        ages: {
          minors: { 'misc:sum-match': { compareTo: ['step.ages.adults'] } },
          adults: { 'misc:sum-match': { fields: ['step.ages.seniors'], compareTo: ['step.ages.minors'] } },
        },
      },
    };

    expect(getSumMatchRules(uiSchema)).toEqual([
      { ...rule, message: 'custom.key' },
      { path: 'step.ages.minors', fields: ['step.ages.minors'], compareTo: ['step.ages.adults'], message: undefined },
      { path: 'step.ages.adults', fields: ['step.ages.seniors'], compareTo: ['step.ages.minors'], message: undefined },
    ]);
  });
});

describe('sum match state', () => {
  const rule = {
    path: 'step.status',
    fields: ['step.genders.women', 'step.genders.men'],
    compareTo: ['step.ages.minors', 'step.ages.adults'],
  };
  const t = (key: string, options?: Record<string, string>) => (options ? `${key} ${JSON.stringify(options)}` : key);

  test('hasSumMatchValuesOnBothSides requires a value in each group', () => {
    expect(hasSumMatchValuesOnBothSides({ step: { ages: { minors: 3 } } }, rule)).toBe(false);
    expect(hasSumMatchValuesOnBothSides({ step: { ages: { minors: 3 }, genders: { men: '' } } }, rule)).toBe(false);
    expect(hasSumMatchValuesOnBothSides({ step: { ages: { minors: 3 }, genders: { men: 0 } } }, rule)).toBe(true);
  });

  test('getSumMatchState is undefined while every field is empty', () => {
    expect(getSumMatchState({}, rule, t)).toBeUndefined();
  });

  test('getSumMatchState reports a match with the default or configured success message', () => {
    const data = { step: { ages: { minors: 3, adults: 5 }, genders: { women: 4, men: 4 } } };

    expect(getSumMatchState(data, rule, t)).toEqual({ message: 'sum_match.match', status: 'match' });
    expect(getSumMatchState(data, { ...rule, successMessage: 'custom.match' }, t)).toEqual({
      message: 'custom.match',
      status: 'match',
    });
  });

  test('getSumMatchState interpolates both sums and their difference on mismatch', () => {
    const data = { step: { ages: { minors: 300, adults: 60 }, genders: { women: '170,5', men: 180 } } };

    expect(getSumMatchState(data, { ...rule, message: 'custom.mismatch' }, t)).toEqual({
      message: 'custom.mismatch {"difference":"9,5","left":"350,5","right":"360"}',
      status: 'mismatch',
    });
  });

  test('findSumMatchRule finds the rule comparing any of the fields', () => {
    expect(findSumMatchRule([rule], ['step.ages.adults'])).toBe(rule);
    expect(findSumMatchRule([rule], ['step.genders.women', 'step.other'])).toBe(rule);
    expect(findSumMatchRule([rule], ['step.other'])).toBeUndefined();
  });
});

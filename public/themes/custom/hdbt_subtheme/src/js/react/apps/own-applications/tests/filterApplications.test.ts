import { describe, expect, test } from 'vitest';
import { DEFAULT_SORT } from '../enum/SortOptions';
import { filterApplications } from '../helpers/filterApplications';
import { createApplication, createApplications } from './testData';

const numbers = (applications: { applicationNumber: string }[]) => applications.map((a) => a.applicationNumber);

describe('filterApplications', () => {
  const applications = createApplications(3);

  test('sorts by submitted time, newest first, by default', () => {
    expect(numbers(filterApplications(applications, '', false, DEFAULT_SORT))).toEqual([
      'LOCALT-060-0000003',
      'LOCALT-060-0000002',
      'LOCALT-060-0000001',
    ]);
  });

  test('sorts by the selected field and direction', () => {
    const result = filterApplications(applications, '', false, 'oldest_first');
    expect(numbers(result)).toEqual(['LOCALT-060-0000001', 'LOCALT-060-0000002', 'LOCALT-060-0000003']);
  });

  test('searches name, status, number and date case-insensitively', () => {
    expect(numbers(filterApplications(applications, 'application 2', false, DEFAULT_SORT))).toEqual([
      'LOCALT-060-0000002',
    ]);
    expect(numbers(filterApplications(applications, '0000003', false, DEFAULT_SORT))).toEqual(['LOCALT-060-0000003']);
    expect(filterApplications(applications, ' RECEIVED ', false, DEFAULT_SORT)).toHaveLength(3);
    expect(numbers(filterApplications(applications, '01.01.2025', false, DEFAULT_SORT))).toEqual([
      'LOCALT-060-0000001',
    ]);
  });

  test('shows only processed applications when requested', () => {
    const processing = createApplication({ applicationNumber: 'PROCESSING-1', status: 'PROCESSING' });
    const result = filterApplications([...applications, processing], '', true, DEFAULT_SORT);
    expect(numbers(result)).toEqual(['PROCESSING-1']);
  });

  test('does not mutate the given array', () => {
    const copy = [...applications];
    filterApplications(applications, '', false, DEFAULT_SORT);
    expect(applications).toEqual(copy);
  });
});

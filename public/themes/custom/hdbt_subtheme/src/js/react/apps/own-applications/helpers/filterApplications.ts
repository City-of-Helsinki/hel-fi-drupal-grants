import AppSettings from '../enum/AppSettings';
import type { SortValue } from '../enum/SortOptions';
import type { ApplicationItem } from '../types/ApplicationItem';

type SortField = 'applicationNumber' | 'formName' | 'statusLabel' | 'submittedSortable';

const sortFields: Record<SortValue, { field: SortField; direction: 'asc' | 'desc' }> = {
  newest_first: { field: 'submittedSortable', direction: 'desc' },
  oldest_first: { field: 'submittedSortable', direction: 'asc' },
  status_asc: { field: 'statusLabel', direction: 'asc' },
  status_desc: { field: 'statusLabel', direction: 'desc' },
  name_asc: { field: 'formName', direction: 'asc' },
  name_desc: { field: 'formName', direction: 'desc' },
  number_asc: { field: 'applicationNumber', direction: 'asc' },
  number_desc: { field: 'applicationNumber', direction: 'desc' },
};

const matchesSearch = (application: ApplicationItem, search: string) => {
  const searchable = [
    application.formName,
    application.statusLabel,
    application.applicationNumber,
    application.submitted,
  ];
  return searchable.some((value) => value?.toLowerCase().includes(search));
};

/**
 * Filters and sorts the applications.
 *
 * @param applications - The applications.
 * @param search - Free text search, matched against name, status, number and date.
 * @param onlyProcessing - Show only applications that are being processed.
 * @param sort - The sort option.
 * @return The filtered and sorted applications.
 */
export const filterApplications = (
  applications: ApplicationItem[],
  search: string,
  onlyProcessing: boolean,
  sort: SortValue,
): ApplicationItem[] => {
  const searchValue = search.trim().toLowerCase();
  const { field, direction } = sortFields[sort];

  return applications
    .filter((application) => !onlyProcessing || application.status === AppSettings.processingStatus)
    .filter((application) => !searchValue || matchesSearch(application, searchValue))
    .sort((a, b) => {
      const result = (a[field] ?? '').localeCompare(b[field] ?? '');
      return direction === 'asc' ? result : -result;
    });
};

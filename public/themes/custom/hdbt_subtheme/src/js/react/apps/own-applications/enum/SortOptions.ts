import type OptionType from '../types/OptionType';

export type SortValue =
  | 'newest_first'
  | 'oldest_first'
  | 'status_asc'
  | 'status_desc'
  | 'name_asc'
  | 'name_desc'
  | 'number_asc'
  | 'number_desc';

export const DEFAULT_SORT: SortValue = 'newest_first';

const SortOptions: (OptionType & { value: SortValue })[] = [
  {
    label: Drupal.t('Submitted time (newest first)', {}, { context: 'grants_handler' }),
    value: 'newest_first',
  },
  {
    label: Drupal.t('Submitted time (oldest first)', {}, { context: 'grants_handler' }),
    value: 'oldest_first',
  },
  { label: Drupal.t('Status (a-z)', {}, { context: 'grants_handler' }), value: 'status_asc' },
  { label: Drupal.t('Status (z-a)', {}, { context: 'grants_handler' }), value: 'status_desc' },
  { label: Drupal.t('Name (a-z)', {}, { context: 'grants_handler' }), value: 'name_asc' },
  { label: Drupal.t('Name (z-a)', {}, { context: 'grants_handler' }), value: 'name_desc' },
  { label: Drupal.t('Application number (a-z)', {}, { context: 'grants_handler' }), value: 'number_asc' },
  { label: Drupal.t('Application number (z-a)', {}, { context: 'grants_handler' }), value: 'number_desc' },
];

export default SortOptions;

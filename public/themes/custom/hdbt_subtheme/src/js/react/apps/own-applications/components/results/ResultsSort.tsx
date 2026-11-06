import { Select } from 'hds-react';
import { defaultSelectTheme } from '@/react/common/constants/selectTheme';
import { getCurrentLanguage } from '@/react/common/helpers/GetCurrentLanguage';
import SortOptions, { DEFAULT_SORT, type SortValue } from '../../enum/SortOptions';

type ResultsSortProps = {
  setSort: (sort: SortValue) => void;
  sort: SortValue;
};

const ResultsSort = ({ setSort, sort }: ResultsSortProps) => (
  <Select
    id='applicationListSort'
    clearable={false}
    onChange={(_selectedOptions, clickedOption) => {
      setSort((clickedOption?.value as SortValue) || DEFAULT_SORT);
    }}
    options={SortOptions}
    theme={defaultSelectTheme}
    texts={{
      label: Drupal.t('Sort', {}, { context: 'grants_handler' }),
      language: getCurrentLanguage(window.drupalSettings.path.currentLanguage),
    }}
    value={[SortOptions.find((option) => option.value === sort) ?? SortOptions[0]]}
  />
);

export default ResultsSort;

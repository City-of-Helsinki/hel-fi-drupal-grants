import { Button, Checkbox, TextInput } from 'hds-react';
import { type FormEvent, useMemo, useState } from 'react';
import { defaultCheckboxStyle } from '@/react/common/constants/checkboxStyle';
import { defaultTextInputStyle } from '@/react/common/constants/textInputStyle';
import ResultsEmpty from '@/react/common/ResultsEmpty';
import ResultsHeader from '@/react/common/ResultsHeader';
import { DEFAULT_SORT, type SortValue } from '../enum/SortOptions';
import { filterApplications } from '../helpers/filterApplications';
import ResultsList from '../components/results/ResultsList';
import ResultsSort from '../components/results/ResultsSort';
import type { ApplicationItem } from '../types/ApplicationItem';

const SentContainer = ({ applications }: { applications: ApplicationItem[] }) => {
  const [searchInput, setSearchInput] = useState('');
  const [stagedOnlyProcessing, setStagedOnlyProcessing] = useState(false);
  const [search, setSearch] = useState('');
  const [onlyProcessing, setOnlyProcessing] = useState(false);
  const [sort, setSort] = useState<SortValue>(DEFAULT_SORT);

  const filteredApplications = useMemo(
    () => filterApplications(applications, search, onlyProcessing, sort),
    [applications, search, onlyProcessing, sort],
  );

  const onSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSearch(searchInput);
    setOnlyProcessing(stagedOnlyProcessing);
  };

  return (
    <section className='own-applications' id='own-applications__sent'>
      <div className='container'>
        <h2 className='own-applications__title'>
          {Drupal.t('Sent applications', {}, { context: 'grants_oma_asiointi' })}
        </h2>
        {applications.length ? (
          <>
            {/* biome-ignore lint/a11y/useSemanticElements: We use form with role for now */}
            <form className='hdbt-search--react__form-container' role='search' onSubmit={onSubmit}>
              <TextInput
                className='hdbt-search--react__text-field'
                id='applicationListFilter'
                label={Drupal.t('Search for an application', {}, { context: 'grants_handler' })}
                onChange={(e) => setSearchInput(e.target.value)}
                value={searchInput}
                style={defaultTextInputStyle}
              />
              <div className='react-search__checkbox-filter-container'>
                <fieldset className='hdbt-search--react__fieldset'>
                  <Checkbox
                    className='react-search__checkbox'
                    checked={stagedOnlyProcessing}
                    id='checkbox-processed'
                    onChange={(e) => setStagedOnlyProcessing(e.target.checked)}
                    label={Drupal.t('Show only applications being processed', {}, { context: 'grants_handler' })}
                    style={defaultCheckboxStyle}
                  />
                </fieldset>
              </div>
              <Button className='hdbt-search--react__submit-button' type='submit'>
                {Drupal.t('Search', {}, { context: 'React search: submit button label' })}
              </Button>
            </form>
            {filteredApplications.length ? (
              <>
                <ResultsHeader
                  actions={<ResultsSort sort={sort} setSort={setSort} />}
                  actionsClass='hdbt-search--react__results--sort'
                  resultText={`${filteredApplications.length} ${Drupal.t('Applications', {}, { context: 'grants_handler' })}`}
                />
                <ResultsList applications={filteredApplications} resetKey={`${search}|${onlyProcessing}|${sort}`} />
              </>
            ) : (
              <ResultsEmpty />
            )}
          </>
        ) : (
          <p>{Drupal.t('No sent applications.', {}, { context: 'grants_handler' })}</p>
        )}
      </div>
    </section>
  );
};

export default SentContainer;

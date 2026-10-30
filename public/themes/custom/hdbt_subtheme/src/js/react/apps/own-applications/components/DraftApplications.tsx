import type { ApplicationItem } from '../types/ApplicationItem';
import { ApplicationList } from './ApplicationList';

export const DraftApplications = ({ applications }: { applications: ApplicationItem[] }) => (
  <section className='own-applications' id='own-applications__drafts'>
    <div className='container'>
      <h2 className='own-applications__title'>
        {Drupal.t('Applications in progress', {}, { context: 'grants_oma_asiointi' })}
      </h2>
      {applications.length ? (
        <ApplicationList applications={applications} />
      ) : (
        <p>{Drupal.t('No applications in progress.', {}, { context: 'grants_handler' })}</p>
      )}
    </div>
  </section>
);

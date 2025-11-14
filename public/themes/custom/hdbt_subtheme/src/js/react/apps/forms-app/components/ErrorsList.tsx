import { useAtomValue } from 'jotai';
import { Notification } from 'hds-react';
import { errorsAtom } from '../store';

export const ErrorsList = () => {
  const errors = useAtomValue(errorsAtom);

  if (!errors.length) {
    return null;
  }

  return (
    <Notification
      label={Drupal.t('Missing or incomplete information')}
      type='error'
      style={{ marginTop: 'var(--spacing-2-xl)' }}
    >
      <ul>
        {errors.map(([index, error]) => (
          <li key={error.schemaPath}>
            {Drupal.t(
              'Error on page @page:',
              { '@page': index + 1 },
              { context: 'Grants application: Errors' },
            )}
            {error?.message &&
              ` ${error.message.charAt(0).toUpperCase()}${error.message.slice(1)}`}
          </li>
        ))}
      </ul>
    </Notification>
  );
};

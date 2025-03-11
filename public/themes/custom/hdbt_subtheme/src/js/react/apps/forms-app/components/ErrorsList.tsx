import { useAtomValue } from 'jotai';
import { Notification } from 'hds-react';
import { getErrorsAtom } from '../store';

export const ErrorsList = () => {
  const errors = useAtomValue(getErrorsAtom);

  if (!errors.length) {
    return null;
  }

  return (
    <Notification
      label={Drupal.t('Missing or incomplete information')}
      type='error'
    >
      <ul>
        {errors.map(([index, error]) => (
          <li key={error.schemaPath}>
            {`${Drupal.t('Error on page')} ${index + 1}.`}
            {error?.message &&
              ` ${error.message.charAt(0).toUpperCase()}${error.message.slice(1)}`
            }
          </li>
        ))}
      </ul>
    </Notification>
  )
};

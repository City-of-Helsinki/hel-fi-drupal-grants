import { Notification } from 'hds-react';

export const GeneralError = () => (
    <Notification
      type='error'
      label={Drupal.t('Error')}
    >
      {Drupal.t('The application ran into an unrecoverable error. Please refresh the page to continue.')}
    </Notification>
  );

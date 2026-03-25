import { Button, ButtonPresetTheme, ButtonVariant, IconCopy, IconPrinter } from 'hds-react';
import { useAtomValue } from 'jotai';

import { getFormConfigAtom, getFormTitleAtom, getSummaryDataAtom } from '../store';
import { useState } from 'react';
import { Requests } from '../Requests';

export const FormSummary = () => {
  const [disableActions, setDisableActions] = useState(false);
  const formTitle = useAtomValue(getFormTitleAtom);
  const summaryData = useAtomValue(getSummaryDataAtom);
  const { applicationNumber, token } = useAtomValue(getFormConfigAtom);

  if (!summaryData) {
    return null;
  }

  const { applicationSubmitted, attachments, handlers, statusUpdates } = summaryData;

  const copyApplication = async () => {
    setDisableActions(true);
    const response = await Requests.DRAFT_APPLICATION_CREATE('58', token, applicationNumber);

    if (response.ok) {
      const { redirect_url } = await response.json();
      window.location.href = redirect_url;
      return;
    }

    throw new Error('Failed to copy application');
  };

  return (
    <div className='webform-submission-information'>
      <div className='webform-submission-information__row webform-submission-information__row-top'>
        <h4>{formTitle}</h4>
        <div className='webform-submission-information__supportlinks'>
          <Button
            iconStart={<IconPrinter />}
            theme={ButtonPresetTheme.Black}
            variant={ButtonVariant.Supplementary}
            onClick={() => {
              window.location.href = drupalSettings.grants_react_form.print_url as string;
            }}
          >
            {Drupal.t('Print application', {}, { context: 'Grants application: Submitted form' })}
          </Button>
          <Button
            disabled={disableActions}
            iconStart={<IconCopy />}
            theme={ButtonPresetTheme.Black}
            onClick={copyApplication}
            variant={ButtonVariant.Supplementary}
          >
            {Drupal.t('Copy application', {}, { context: 'Grants application: Submitted form' })}
          </Button>
        </div>
      </div>
      <div className='webform-submission-information__row webform-submission-information__row-main'>
        <div>
          <h5>{Drupal.t('Application number', {}, { context: 'Grants application: Submitted form' })}</h5>
          {applicationNumber}
          <h5>{Drupal.t('Sent date', {}, { context: 'Grants application: Submitted form' })}</h5>
          {applicationSubmitted}
          <h5>{Drupal.t('Handler information', {}, { context: 'Grants application: Submitted form' })}</h5>
          {handlers?.length
            ? handlers.join(', ')
            : Drupal.t('No handler information set', {}, { context: 'Grants application: Submitted form' })}
        </div>
        <div>
          <h5>{Drupal.t('Application statuses', {}, { context: 'Grants application: Submitted form' })}</h5>
          {statusUpdates?.length && (
            <ul className='application-status-history'>
              {statusUpdates.map((status) => (
                <li key={status}>{status}</li>
              ))}
            </ul>
          )}
        </div>
        <div>
          <h5>{Drupal.t('Attachments', {}, { context: 'Grants application: Submitted form' })}</h5>
          {attachments?.length && (
            <ul className='application-attachment-list'>
              {attachments.map((attachment) => {
                return <li key={attachment}>{attachment}</li>;
              })}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
};

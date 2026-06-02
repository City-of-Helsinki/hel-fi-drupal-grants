import { Button, ButtonVariant, IconCopy, IconPrinter } from 'hds-react';
import { useAtomValue } from 'jotai';
import { useState } from 'react';
import { supplementaryButtonTheme } from '@/react/common/constants/buttonTheme';
import { Requests } from '../Requests';
import { getFormConfigAtom, getFormTitleAtom, getSummaryDataAtom } from '../store';

const getHandlerKey = (index: number) => {
  switch (index) {
    case 0:
      return 'handler_name';
    case 1:
      return 'handler_phone';
    case 2:
      return 'handler_email';
    default:
      return `handler_${index + 1}`;
  }
};

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

  const latestHandler =
    handlers?.length && Array.isArray(handlers[handlers.length - 1]) ? handlers[handlers.length - 1] : null;

  return (
    <div className='hdbt-react-form__submission-info'>
      <div className='hdbt-react-form__submission-info__row hdbt-react-form__submission-info__row--top'>
        <h4>{formTitle}</h4>
        <div className='hdbt-react-form__submission-info__row__supportlinks'>
          <Button
            iconStart={<IconPrinter />}
            theme={supplementaryButtonTheme}
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
            theme={supplementaryButtonTheme}
            onClick={copyApplication}
            variant={ButtonVariant.Supplementary}
          >
            {Drupal.t('Copy application', {}, { context: 'Grants application: Submitted form' })}
          </Button>
        </div>
      </div>
      <div className='hdbt-react-form__submission-info__row hdbt-react-form__submission-info__row--main'>
        <div>
          <h5>{Drupal.t('Application number', {}, { context: 'Grants application: Submitted form' })}</h5>
          {applicationNumber}
          <h5>{Drupal.t('Sent date', {}, { context: 'Grants application: Submitted form' })}</h5>
          {applicationSubmitted}
          <h5>{Drupal.t('Handler information', {}, { context: 'Grants application: Submitted form' })}</h5>
          {latestHandler ? (
            latestHandler.map((value, index) => <div key={getHandlerKey(index)}>{value}</div>)
          ) : (
            <div>{Drupal.t('No handler information set')}</div>
          )}
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

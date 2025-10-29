import { Button, ButtonPresetTheme, ButtonVariant, IconCopy, IconPrinter } from 'hds-react';
import { DateTime } from 'luxon';
import { RJSFSchema } from '@rjsf/utils';
import { useAtomValue } from 'jotai';

import { avus2DataAtom, getFormTitleAtom } from '../store';
import { StatusLabels } from '../enum/StatusLabels';
import { SubmitStates } from '../enum/SubmitStates';

export const FormSummary = ({
  formData,
  schema,
}: {
  formData: any,
  schema: RJSFSchema,
}) => {
  const avus2Data = useAtomValue(avus2DataAtom);
  const formTitle = useAtomValue(getFormTitleAtom);

  const getSentDate = () => {
    const date = avus2Data?.statusUpdates?.find(statusUpdate => statusUpdate.citizenCaseStatus === SubmitStates.RECEIVED);

    if (!date) {
      return '-';
    }

    const dateObject = new Date(date.timeCreated);
    return dateObject.toLocaleString('fi');
  };

  const statusEvents = avus2Data?.events?.filter(event => event.eventType === 'STATUS_UPDATE') || [];
  const statuses = statusEvents.map(event => {
    const date = DateTime.fromISO(event.timeCreated);
    return `${StatusLabels[event.eventDescription]}: ${date.setLocale('fi').toLocaleString(DateTime.DATETIME_MED)}`;
  });

  return (
    <div className='webform-submission-information'>
      <div className='webform-submission-information__row webform-submission-information__row-top'>
        <h4>{formTitle}</h4>
        <div className='webform-submission-information__supportlinks'>
          <Button
            iconStart={<IconPrinter />}
            theme={ButtonPresetTheme.Black}
            variant={ButtonVariant.Supplementary}
          >
            {Drupal.t('Print application', {}, {context: 'Grants application: Submitted form'})}
          </Button>
          <Button
            iconStart={<IconCopy />}
            theme={ButtonPresetTheme.Black}
            variant={ButtonVariant.Supplementary}
          >
            {Drupal.t('Copy application', {}, {context: 'Grants application: Submitted form'})}
          </Button>
        </div>
      </div>
      <div className="webform-submission-information__row webform-submission-information__row-main">
        <div>
          <h5>{Drupal.t('Application number', {}, {context: 'Grants application: Submitted form'})}</h5>
          {formData.applicationNumber}
          <h5>{Drupal.t('Sent date', {}, {context: 'Grants application: Submitted form'})}</h5>
          {getSentDate()}
          <h5>{Drupal.t('Handler information', {}, {context: 'Grants application: Submitted form'})}</h5>
          {Drupal.t('No handler information set', {}, {context: 'Grants application: Submitted form'})}
        </div>
        <div>
          <h5>{Drupal.t('Application statuses', {}, {context: 'Grants application: Submitted form'})}</h5>
          {statuses?.length && <ul className='application-status-history'>
            {statuses.map((status) => <li>{status}</li>)}
          </ul>}
        </div>
        <div>
          <h5>{Drupal.t('Attachments', {}, {context: 'Grants application: Submitted form'})}</h5>
          {avus2Data?.attachmentsInfo?.attachmentsArray?.length && <ul className='application-attachment-list'>
            {avus2Data.attachmentsInfo.attachmentsArray.map(attachment => {
                const [description, name] = attachment;
                return <li>{`${description.value ? `${description.value}, ` : ''}${name.value}`}</li>;
            })}
          </ul>}
        </div>
      </div>
    </div>
  );
}

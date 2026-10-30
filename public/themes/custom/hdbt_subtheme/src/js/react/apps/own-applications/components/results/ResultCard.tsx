import { IconCheckCircle, IconError, IconInfoCircle } from 'hds-react';
import { type JSX, type MouseEvent, useState } from 'react';
import CardItem, { type CardActionType, Metarow } from '@/react/common/Card';
import type TagType from '@/types/TagType';
import type { ApplicationItem } from '../../types/ApplicationItem';
import DeleteDraftDialog from '../DeleteDraftDialog';

const statusColors: Record<string, string> = {
  SENT: 'info',
  SUBMITTED: 'info',
  RECEIVED: 'info',
  PREPARING: 'info',
  PENDING: 'info',
  PROCESSING: 'info',
  READY: 'success',
  RESOLVED: 'success',
  REJECTED: 'error',
  DELETED: 'error',
  CANCELED: 'error',
  CANCELLED: 'error',
  CLOSED: 'error',
};

const statusIcons: Record<string, JSX.Element> = {
  info: <IconInfoCircle />,
  success: <IconCheckCircle />,
  error: <IconError />,
};

const getTags = ({ errorType, status, statusLabel, unreadMessages }: ApplicationItem): TagType[] => {
  const color = statusColors[status.toUpperCase()];
  const tags: TagType[] = [{ tag: statusLabel, color, iconStart: color ? statusIcons[color] : undefined }];

  if (status === 'DRAFT' && errorType) {
    tags.push({
      tag:
        errorType === 'ARCHIVED'
          ? Drupal.t('Application form expired', {}, { context: 'grants_handler' })
          : Drupal.t('Application period ended', {}, { context: 'grants_handler' }),
      color: 'alert',
      iconStart: <IconInfoCircle />,
    });
  }

  if (unreadMessages) {
    tags.push({ tag: unreadMessages, color: 'alert' });
  }

  return tags;
};

const getActions = (
  { applicationNumber, formName, links, status }: ApplicationItem,
  onDelete: (e: MouseEvent<HTMLAnchorElement>) => void,
): CardActionType[] => {
  const actions: CardActionType[] = [];

  if (status === 'DRAFT') {
    if (links.edit) {
      actions.push({
        label: Drupal.t('Edit'),
        ariaLabel: Drupal.t(
          'Edit application @number, @name',
          { '@number': applicationNumber, '@name': formName },
          { context: 'grants_handler' },
        ),
        url: links.edit,
        icon: 'pen-line',
      });
    }
    if (links.delete) {
      actions.push({
        label: Drupal.t('Delete'),
        ariaLabel: Drupal.t(
          'Delete application @number, @name',
          { '@number': applicationNumber, '@name': formName },
          { context: 'grants_handler' },
        ),
        url: links.delete,
        icon: 'trash',
        className: `application-delete-link-${applicationNumber}`,
        onClick: onDelete,
      });
    }
    return actions;
  }

  if (links.view) {
    actions.push({
      label: Drupal.t('View'),
      ariaLabel: Drupal.t(
        'View application @number, @name',
        { '@number': applicationNumber, '@name': formName },
        { context: 'grants_handler' },
      ),
      url: links.view,
      icon: 'eye',
    });
  }
  if (links.print) {
    actions.push({
      label: Drupal.t('Print'),
      ariaLabel: Drupal.t(
        'Print application @number, @name',
        { '@number': applicationNumber, '@name': formName },
        { context: 'grants_handler' },
      ),
      url: links.print,
      icon: 'printer',
    });
  }
  return actions;
};

const ResultCard = ({ application }: { application: ApplicationItem }) => {
  const { applicationNumber, formName, links, status, submitted, submittedSortable } = application;
  const [deleteOpener, setDeleteOpener] = useState<HTMLElement | null>(null);

  const openDeleteDialog = (e: MouseEvent<HTMLAnchorElement>) => {
    e.preventDefault();
    setDeleteOpener(e.currentTarget);
  };

  return (
    <>
      <CardItem
        cardModifierClass={`card--border application-list__item application-list__item--${status.toLowerCase()}`}
        cardTitle={formName}
        cardTitleLevel={3}
        cardCategoryTag={getTags(application)}
        cardActions={getActions(application, openDeleteDialog)}
        customMetaRows={{
          top: [
            <Metarow
              key='submitted'
              icon='clock'
              label={
                status === 'DRAFT'
                  ? Drupal.t('Draft saved time', {}, { context: 'grants_handler' })
                  : Drupal.t('Submission date', {}, { context: 'grants_handler' })
              }
              content={
                <>
                  {submitted}
                  <span className='application-list__item--submitted'>{submittedSortable}</span>
                </>
              }
            />,
            <Metarow
              key='number'
              icon='document'
              label={Drupal.t('Application number', {}, { context: 'grants_handler' })}
              content={
                <span className='application-list__item--number application-list__item--application-number'>
                  {applicationNumber}
                </span>
              }
            />,
          ],
        }}
      />
      {links.delete && (
        <DeleteDraftDialog
          close={() => setDeleteOpener(null)}
          focusAfterCloseElement={deleteOpener ?? undefined}
          isOpen={deleteOpener !== null}
          url={links.delete}
        />
      )}
    </>
  );
};

export default ResultCard;

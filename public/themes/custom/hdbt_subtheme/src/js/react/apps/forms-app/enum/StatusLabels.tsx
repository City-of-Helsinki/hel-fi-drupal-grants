import { SubmitStates } from './SubmitStates';

export const StatusLabels = {
  [SubmitStates.CANCELLED]: Drupal.t('Cancelled', {}, {context: 'Grants application: Status label'}),
  [SubmitStates.DRAFT]: Drupal.t('Draft', {}, {context: 'Grants application: Status label'}),
  [SubmitStates.PENDING]: Drupal.t('Pending', {}, {context: 'Grants application: Status label'}),
  [SubmitStates.PREPARING]: Drupal.t('In Preparation', {}, {context: 'Grants application: Status label'}),
  [SubmitStates.PROCESSING]: Drupal.t('Processing', {}, {context: 'Grants application: Status label'}),
  [SubmitStates.RECEIVED]: Drupal.t('Received', {}, {context: 'Grants application: Status label'}),
  [SubmitStates.REJECTED]: Drupal.t('Rejected', {}, {context: 'Grants application: Status label'}),
  [SubmitStates.RESOLVED]: Drupal.t('Resolved', {}, {context: 'Grants application: Status label'}),
  [SubmitStates.SUBMITTED]: Drupal.t('Sent - waiting for confirmation', {}, {context: 'Grants application: Status label'}),
};

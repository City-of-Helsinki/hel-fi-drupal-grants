import { RJSFSchema } from '@rjsf/utils';
import { useAtomValue } from 'jotai';

import { getApplicationNumberAtom } from '../store';
import { Preview } from './Preview';
import { StatusLabel } from './StatusLabel';
import { FormSummary } from './FormSummary';

export const SubmittedForm = ({
  formData,
  schema,
}: {
  formData: any,
  schema: RJSFSchema
}) => {
  const applicationNumber = useAtomValue(getApplicationNumberAtom);

  return (
    <>
      <StatusLabel />
      <h1>{schema.title}</h1>
      <div className='webform-submission__application_id'>
        <h2>{Drupal.t('Application number', {}, {context: 'Grants application: Submitted form'})}</h2>
        <div className='webform-submission__application_id--body'>
          {applicationNumber.toString().toUpperCase()}
        </div>
      </div>
      <h3>{Drupal.t('Application info', {}, {context: 'Grants application: Submitted form'})}</h3>
      <FormSummary {...{formData, schema}} />
      <h3>{Drupal.t('Application', {}, {context: 'Grants application: Submitted form'})}</h3>
      <p>{Drupal.t('Here you can see details of your application', {}, {context: 'Grants application: Submitted form'})}</p>
      <Preview
        formData={formData}
        schema={schema}
      />
    </>
  );
}

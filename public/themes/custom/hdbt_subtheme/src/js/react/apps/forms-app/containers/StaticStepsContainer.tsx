import { useAtomValue, WritableAtom } from 'jotai';
import { RJSFSchema } from '@rjsf/utils';

import { getCurrentStepAtom } from '../store';

export const StaticStepsContainer = ({
  formDataAtom,
  schema,
}: {
  formDataAtom: WritableAtom<any, [update: unknown], any>;
  schema: RJSFSchema;
}) => {
  const currentStep = useAtomValue(getCurrentStepAtom)[1];

  switch(currentStep.id) {
    case 'preview':
      return (
        <h2>{Drupal.t('Confirm, preview and submit', {}, {context: 'Grants application: Steps'})}</h2>
      );
    //  At least for now, this page is never accessible.
    case 'ready':
      return null;
    default:
      return null;
  }
}

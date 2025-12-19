// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
// biome-ignore-all lint/suspicious/noExplicitAny: @todo UHF-12501
import { useAtomValue, type WritableAtom } from 'jotai';
import type { RJSFSchema } from '@rjsf/utils';

import { getCurrentStepAtom } from '../store';

export const StaticStepsContainer = ({
  formDataAtom,
  schema,
}: {
  formDataAtom: WritableAtom<any, [update: unknown], any>;
  schema: RJSFSchema;
}) => {
  const currentStep = useAtomValue(getCurrentStepAtom)[1];

  switch (currentStep.id) {
    case 'preview':
      return (
        <h2 className='grants-form__page-title'>
          {Drupal.t(
            'Confirm, preview and submit',
            {},
            { context: 'Grants application: Steps' },
          )}
        </h2>
      );
    //  At least for now, this page is never accessible.
    case 'ready':
      return null;
    default:
      return null;
  }
};

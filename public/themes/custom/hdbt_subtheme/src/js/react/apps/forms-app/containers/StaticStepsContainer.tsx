import { useAtomValue } from 'jotai';
import { RJSFSchema } from '@rjsf/utils';

import { getCurrentStepAtom } from '../store';
import { Preview } from '../components/StaticSteps/Preview';
import { Ready } from '../components/StaticSteps/Ready';

export const StaticStepsContainer = ({
  formData,
  schema,
}: {
  formData: any,
  schema: RJSFSchema,
}) => {
  const currentStep = useAtomValue(getCurrentStepAtom)[1];

  switch(currentStep.id) {
    case 'preview':
      return (
        <Preview {...{formData, schema}} />
      );
    case 'ready':
      return (
        <div>
          <Ready />
          <Preview {...{formData, schema}} />
        </div>
      );
    default:
      return null;
  }
}

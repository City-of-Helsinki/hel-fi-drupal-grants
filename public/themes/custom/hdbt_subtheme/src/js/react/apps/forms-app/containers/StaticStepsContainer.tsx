import { useAtomValue, WritableAtom } from 'jotai';
import { RJSFSchema } from '@rjsf/utils';

import { getCurrentStepAtom } from '../store';
import { Preview } from '../components/StaticSteps/Preview';
import { Ready } from '../components/StaticSteps/Ready';

export const StaticStepsContainer = ({
  formDataAtom,
  schema,
}: {
  formDataAtom: WritableAtom<any, [update: unknown], any>;
  schema: RJSFSchema;
}) => {
  const formData= useAtomValue(formDataAtom);
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

import Form from '@rjsf/core';
import { RefObject } from 'react';
import { useAtomValue } from 'jotai';
import { RJSFSchema } from '@rjsf/utils';

import { getCurrentStepAtom } from '../store';
import { Preview } from '../components/StaticSteps/Preview';

export const StaticStepsContainer = ({
  formRef,
  schema,
}: {
  formRef: RefObject<Form>,
  schema: RJSFSchema,
}) => {
  const currentStep = useAtomValue(getCurrentStepAtom)[1];

  if (!formRef.current) {
    return null;
  }

  switch(currentStep.id) {
    case 'preview':
      return (
        <Preview {...{formRef, schema}} />
      );
    case 'ready':
      return (
        <div>
          <div>Submitted form: </div>
          <Preview {...{formRef, schema}} />
        </div>
      );
    default:
      return null;
  }
}

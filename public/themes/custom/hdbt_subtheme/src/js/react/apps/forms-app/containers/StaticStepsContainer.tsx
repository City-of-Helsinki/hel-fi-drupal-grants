import { useAtomValue } from 'jotai';
import { formStateAtom, getCurrentStepAtom } from '../store';
import { Button } from 'hds-react';

export const StaticStepsContainer = ({
  formRef
}: any) => {
  const [currentStepIndex, currentStep] = useAtomValue(getCurrentStepAtom);

  switch(currentStep.id) {
    case 'preview':
      return (
        <div>
          <pre>
            {JSON.stringify(formRef.current.state.formData)}
          </pre>
        </div>
      );
    case 'ready':
      return (
        <div>
          <div>Submitted form: </div>
          <pre>{JSON.stringify(formRef.current.state.formData)}</pre>
        </div>
      );
    default:
      return null;
  }
}

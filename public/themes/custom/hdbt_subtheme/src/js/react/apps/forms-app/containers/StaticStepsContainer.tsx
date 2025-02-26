import { useAtomValue } from 'jotai';
import { getCurrentStepAtom } from '../store';

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

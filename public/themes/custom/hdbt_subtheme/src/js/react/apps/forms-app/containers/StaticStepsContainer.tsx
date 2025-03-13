import { useAtomValue } from 'jotai';
import { getCurrentStepAtom } from '../store';
import { PreviewContainer } from './PreviewContainer';

export const StaticStepsContainer = ({
  formRef
}: any) => {
  const currentStep = useAtomValue(getCurrentStepAtom)[1];

  if (!formRef.current) {
    return null;
  }

  switch(currentStep.id) {
    case 'preview':
      return (
        <PreviewContainer />
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

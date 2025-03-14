import { useAtomValue, useSetAtom } from 'jotai';
import { Button, ButtonPresetTheme, ButtonVariant, IconDownloadCloud, IconTrash } from 'hds-react';
import { ValidationData } from '@rjsf/utils';
import { getCurrentStepAtom, getErrorsAtom, getStepsAtom, setStepAtom } from '../store';
import { keyErrorsByStep } from '../utils';

export const FormActions = ({
  validatePartialForm,
}: {
  validatePartialForm: () => ValidationData<any>|undefined
}) => {
  const steps = useAtomValue(getStepsAtom);
  const [currentStepIndex, { id: currentStepId }] = useAtomValue(getCurrentStepAtom);
  const errors = useAtomValue(getErrorsAtom);
  const setStep = useSetAtom(setStepAtom);

  const nextPageAction = () => {
    const validateResult = validatePartialForm();

    if (!validateResult) {
      setStep(currentStepIndex + 1);
      return;
    }

    const { errors: resultErrors } = validateResult;
    const keyedErrors = keyErrorsByStep(resultErrors, steps);
    const errorPageIndices = keyedErrors.map(([index]) => index);
    if (!errorPageIndices.includes(currentStepIndex)) {
      setStep(currentStepIndex + 1);
    }
  };

  return (
    <div className='form-actions form-wrapper'>
      <div className='actions'>
        <Button
          iconStart={<IconTrash />}
          theme={ButtonPresetTheme.Black}
          type='button'
          variant={ButtonVariant.Supplementary}
        >
          {Drupal.t('Delete draft')}
        </Button>
        <Button
          iconStart={<IconDownloadCloud />}
          theme={ButtonPresetTheme.Black}
          type='button'
          variant={ButtonVariant.Supplementary}
        >
          {Drupal.t('Save as draft')}
        </Button>
        {
          (currentStepIndex > 0 && currentStepId !== 'ready') &&
          <Button
            onClick={() => setStep(currentStepIndex - 1)}
            theme={ButtonPresetTheme.Black}
            type='button'
            variant={ButtonVariant.Primary}
          >
            {Drupal.t('Previous')}
          </Button>
        }
        {
          currentStepId !== 'ready' &&
            (
              currentStepId === 'preview' ?
              <Button
                disabled={Boolean(errors?.length) || false}
                theme={ButtonPresetTheme.Black}
                type='submit'
                variant={ButtonVariant.Primary}
              >
                {Drupal.t('Submit')}
              </Button> :
              <Button
                onClick={nextPageAction}
                theme={ButtonPresetTheme.Black}
                type='button'
                variant={ButtonVariant.Primary}
              >
                {Drupal.t('Next')}
              </Button>
            )
        }
      </div>
    </div>
  );
};

import { useAtomValue, useSetAtom } from 'jotai';
import { Button, ButtonPresetTheme, ButtonVariant } from 'hds-react';
import { ValidationData } from '@rjsf/utils';
import { SyntheticEvent } from 'react';
import { getCurrentStepAtom, getErrorsAtom, getStepsAtom, setStepAtom } from '../../store';
import { keyErrorsByStep } from '../../utils';
import { SaveDraftButton } from './SaveDraftButton';

export const FormActions = ({
  saveDraft,
  validatePartialForm,
}: {
  saveDraft: () => boolean,
  validatePartialForm: () => ValidationData<any>|undefined
}) => {
  const steps = useAtomValue(getStepsAtom);
  const [currentStepIndex, { id: currentStepId }] = useAtomValue(getCurrentStepAtom);
  const errors = useAtomValue(getErrorsAtom);
  const setStep = useSetAtom(setStepAtom);

  const nextPageAction = (event: SyntheticEvent<HTMLButtonElement>) => {
    event.preventDefault();

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
        {/*
        @todo add back when deleting draft is supported
        <Button
          iconStart={<IconTrash />}
          theme={ButtonPresetTheme.Black}
          type='button'
          variant={ButtonVariant.Supplementary}
        >
          {Drupal.t('Delete draft')}
        </Button> */}
        <SaveDraftButton saveDraft={saveDraft} />
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

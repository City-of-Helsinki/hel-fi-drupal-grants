import { useAtomValue, useSetAtom } from 'jotai';
import { Button, IconTrash, IconAngleLeft, IconAngleRight } from 'hds-react';
import { ValidationData } from '@rjsf/utils';
import { SyntheticEvent } from 'react';
import { getCurrentStepAtom, errorsAtom, getStepsAtom, setStepAtom, finalAcceptanceAtom } from '../../store';
import { keyErrorsByStep } from '../../utils';
import { SaveDraftButton } from './SaveDraftButton';
import { primaryButtonTheme, secondaryButtonTheme } from '@/react/common/constants/buttonTheme';

export const FormActions = ({
  saveDraft,
  validatePartialForm,
}: {
  saveDraft: () => Promise<boolean>;
  validatePartialForm: () => ValidationData<any>|undefined;
}) => {
  const finalAcceptance = useAtomValue(finalAcceptanceAtom);
  const steps = useAtomValue(getStepsAtom);
  const [currentStepIndex, { id: currentStepId }] = useAtomValue(getCurrentStepAtom);
  const errors = useAtomValue(errorsAtom);
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
    <div className='hdbt-form--actions'>
      {/* @todo add back when deleting is supported
      <Button
        iconStart={<IconTrash />}
        theme={secondaryButtonTheme}
        type='button'
      >
        {Drupal.t('Delete draft')}
      </Button> */}
      <SaveDraftButton saveDraft={saveDraft} />
      {
        (currentStepIndex > 0 && currentStepId !== 'ready') &&
        <Button
          className='hdbt-form--pager-button'
          onClick={() => setStep(currentStepIndex - 1)}
          theme={primaryButtonTheme}
          type='button'
          iconStart={<IconAngleLeft />}
        >
          {Drupal.t('Previous')}
        </Button>
      }
      {
        currentStepId !== 'ready' &&
          (
            currentStepId === 'preview' ?
            <Button
              disabled={Boolean(errors?.length) || !finalAcceptance}
              type='submit'
              theme={primaryButtonTheme}
            >
              {Drupal.t('Submit')}
            </Button> :
            <Button
              onClick={nextPageAction}
              className='hdbt-form--pager-button'
              type='button'
              theme={primaryButtonTheme}
              iconEnd={<IconAngleRight />}
            >
              {Drupal.t('Next')}
            </Button>
          )
      }
    </div>
  );
};

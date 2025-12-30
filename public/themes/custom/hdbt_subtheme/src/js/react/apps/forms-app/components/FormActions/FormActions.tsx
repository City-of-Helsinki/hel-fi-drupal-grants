// biome-ignore-all lint/suspicious/noExplicitAny: @todo UHF-12501
import { Button, IconAngleLeft, IconAngleRight } from 'hds-react';
import { useAtomValue, useSetAtom } from 'jotai';
import type { ValidationData } from '@rjsf/utils';
import type { SyntheticEvent } from 'react';

import {
  getCurrentStepAtom,
  errorsAtom,
  getStepsAtom,
  setStepAtom,
  finalAcceptanceAtom,
  isReadOnlyAtom,
} from '../../store';
import { isDraft, keyErrorsByStep } from '../../utils';
import { SaveDraftButton } from './SaveDraftButton';
import { primaryButtonTheme } from '@/react/common/constants/buttonTheme';

export const FormActions = ({
  saveDraft,
  validatePartialForm,
}: {
  saveDraft: () => Promise<void>;
  validatePartialForm: () => ValidationData<any> | undefined;
}) => {
  const readOnly = useAtomValue(isReadOnlyAtom);
  const finalAcceptance = useAtomValue(finalAcceptanceAtom);
  const steps = useAtomValue(getStepsAtom);
  const [currentStepIndex, { id: currentStepId }] =
    useAtomValue(getCurrentStepAtom);
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
      {isDraft() && <SaveDraftButton saveDraft={saveDraft} />}
      {currentStepIndex > 0 && currentStepId !== 'ready' && (
        <Button
          className='hdbt-form--pager-button'
          disabled={readOnly}
          onClick={() => setStep(currentStepIndex - 1)}
          theme={primaryButtonTheme}
          type='button'
          iconStart={<IconAngleLeft />}
        >
          {Drupal.t('Previous')}
        </Button>
      )}
      {currentStepId !== 'ready' &&
        (currentStepId === 'preview' ? (
          <Button
            disabled={Boolean(errors?.length) || !finalAcceptance || readOnly}
            type='submit'
            theme={primaryButtonTheme}
          >
            {isDraft() ? Drupal.t('Submit') : Drupal.t('Send')}
          </Button>
        ) : (
          <Button
            className='hdbt-form--pager-button'
            disabled={readOnly}
            iconEnd={<IconAngleRight />}
            onClick={nextPageAction}
            theme={primaryButtonTheme}
            type='button'
          >
            {currentStepIndex === steps.size - 3
              ? Drupal.t('Preview', {}, { context: 'grants_handler' })
              : Drupal.t('Next', {}, { context: 'Grants application: Steps' })}
          </Button>
        ))}
    </div>
  );
};

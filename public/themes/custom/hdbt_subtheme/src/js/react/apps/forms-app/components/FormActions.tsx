import { useAtomValue, useSetAtom } from 'jotai';
import { getCurrentStepAtom, setStepAtom } from '../store';
import { Button, ButtonPresetTheme, ButtonVariant, IconDownloadCloud, IconTrash } from 'hds-react';
import { RefObject } from 'react';
import Form from '@rjsf/core';

export const FormActions = ({
  formRef,
}: {
  formRef: RefObject<Form>
}) => {
  const [currentStepIndex, { id: currentStepId }] = useAtomValue(getCurrentStepAtom);
  const setStep = useSetAtom(setStepAtom);

  const nextPageAction = () => {
    const data = formRef.current?.state.formData;
    if (!data[currentStepId]) {
      data[currentStepId] = {};
    }

    const passes = formRef.current?.validateFormWithFormData(data);
    if (passes) {
      setStep(currentStepIndex + 1);
    }
  };

  const errors = formRef.current?.state.errors;

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

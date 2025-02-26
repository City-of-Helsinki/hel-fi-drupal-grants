import { SubmitButtonProps, WidgetProps } from '@rjsf/utils';
import { Button, ButtonPresetTheme, ButtonVariant, TextArea as HDSTextArea, TextInput as HDSTextInput, IconDownloadCloud, IconTrash, Select  } from 'hds-react';
import { useAtomValue, useSetAtom } from 'jotai';
import React from 'react';
import { getCurrentStepAtom, setStepAtom } from '../store';

export const TextInput = ({
  defaultValue,
  id,
  label,
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  value,
}: WidgetProps) => {
  const compatibleDefault = typeof defaultValue === 'string' ? defaultValue : undefined;

  return (
    <HDSTextInput
      defaultValue={compatibleDefault}
      errorText={rawErrors?.toString()}
      hideLabel={false}
      id={id}
      invalid={Boolean(rawErrors?.length)}
      label={label}
      name={name}
      onBlur={() => null}
      onChange={(event: React.ChangeEvent<HTMLInputElement>) => onChange(event.target.value)}
      onFocus={() => null}
      readOnly={readonly}
      required={required}
      value={value}
    />
  );
};

export const TextArea = ({
  defaultValue,
  id,
  label,
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  value,
}: WidgetProps) => {
  const compatibleDefault = typeof defaultValue === 'string' ? defaultValue : undefined;

  return (
    <HDSTextArea
      defaultValue={compatibleDefault}
      hideLabel={false}
      id={id}
      invalid={Boolean(rawErrors?.length)}
      label={label}
      name={name}
      onBlur={() => null}
      onChange={(event: React.ChangeEvent<HTMLTextAreaElement>) => onChange(event.target.value)}
      onFocus={() => null}
      readOnly={readonly}
      required={required}
      value={value}
    />
  );
};

export const SelectWidget = ({
  defaultValue,
  id,
  label,
  name,
  onChange,
  options,
  readonly,
  required,
  value,
}: WidgetProps) => (
    <Select
      id={id}
      onBlur={() => null}
      onChange={newValue => onChange(newValue)}
      options={options?.enumOptions ?? []}
      onFocus={() => null}
      required={required}
      value={value}
    />
  );

export const SubmitButton = (props: SubmitButtonProps) => {
  const [currentStepIndex, { id: currentStepId }] = useAtomValue(getCurrentStepAtom);
  const setStep = useSetAtom(setStepAtom);

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
                theme={ButtonPresetTheme.Black}
                type='submit'
                variant={ButtonVariant.Primary}
              >
                {Drupal.t('Submit')}
              </Button> :
              <Button
                onClick={() => setStep(currentStepIndex + 1)}
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

import { SubmitButtonProps, WidgetProps } from '@rjsf/utils';
import { Button, ButtonPresetTheme, ButtonVariant, TextArea as HDSTextArea, TextInput as HDSTextInput, IconDownloadCloud, IconTrash, Select  } from 'hds-react';
import { useAtomValue, useSetAtom } from 'jotai';
import React from 'react';
import { getCurrentStepAtom, setStepAtom } from '../store';

export const TextInput = ({
  id,
  label,
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  value,
}: WidgetProps) => {
  return (
    <HDSTextInput
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
      value={value ?? ''}
    />
  );
};

export const TextArea = ({
  id,
  label,
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  value,
}: WidgetProps) => {
  return (
    <HDSTextArea
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
      value={value ?? ''}
    />
  );
};

export const SelectWidget = ({
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

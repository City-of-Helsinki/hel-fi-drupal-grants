import { SubmitButtonProps, WidgetProps } from '@rjsf/utils';
import { Button, ButtonPresetTheme, ButtonVariant, TextArea as HDSTextArea, TextInput as HDSTextInput, IconDownloadCloud, IconTrash, Select  } from 'hds-react';
import { useAtomValue, useSetAtom } from 'jotai';
import React from 'react';
import { getCurrentStepAtom, setStepAtom } from '../store';

const formatErrors = (rawErrors: string[]|undefined) => {
  if (!rawErrors) {
    return undefined;
  }

  return rawErrors.join('\n');
};

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
      errorText={formatErrors(rawErrors)}
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
      errorText={formatErrors(rawErrors)}
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
  multiple,
  name,
  onChange,
  options,
  rawErrors,
  readonly,
  required,
  value,
}: WidgetProps) => (
    <Select
      id={id}
      invalid={Boolean(rawErrors?.length)}
      multiSelect={multiple}
      onBlur={() => null}
      onChange={newValue => {
        if (!newValue.length) {
          onChange(undefined);
          return;
        }
        if (multiple) {
          onChange(newValue.map(option => option.value));
          return;
        }

        onChange(newValue[0].value);
      }}
      onFocus={() => null}
      options={options?.enumOptions ?? []}
      required={required}
      texts={
        rawErrors ? {
          error: formatErrors(rawErrors),
        } : undefined
      }
      value={value}
    />
  );

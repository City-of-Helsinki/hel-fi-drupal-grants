import { WidgetProps } from '@rjsf/utils';
import { TextArea as HDSTextArea, TextInput as HDSTextInput, Select, FileInput as HDSFileInput  } from 'hds-react';
import { ChangeEvent } from 'react';

export const formatErrors = (rawErrors: string[]|undefined) => {
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
}: WidgetProps) => (
    <HDSTextInput
      errorText={formatErrors(rawErrors)}
      hideLabel={false}
      id={id}
      invalid={Boolean(rawErrors?.length)}
      label={label}
      name={name}
      onBlur={() => null}
      onChange={(event: ChangeEvent<HTMLInputElement>) => onChange(event.target.value)}
      onFocus={() => null}
      readOnly={readonly}
      required={required}
      value={value ?? ''}
    />
  );

export const TextArea = ({
  id,
  label,
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  value,
}: WidgetProps) => (
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

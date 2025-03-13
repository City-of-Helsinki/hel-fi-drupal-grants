import { WidgetProps } from '@rjsf/utils';
import { TextArea as HDSTextArea, TextInput as HDSTextInput, Select  } from 'hds-react';
import { useAtomValue } from 'jotai';
import { ChangeEvent } from 'react';
import { getAccountsAtom, getAddressesAtom, getOfficialsAtom } from '../store';

/**
 * Transform raw errors to a more readable format.
 *
 * @param {array|undefined} rawErrors
 * @returns {string} - Resulting error message
 */
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

type SelectWidgetProps = WidgetProps & {
  assistive?: string;
};

export const SelectWidget = ({
  assistive,
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
}: SelectWidgetProps) => (
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
    texts={{
      assistive,
      error: rawErrors ? formatErrors(rawErrors) : undefined,
      label: label ?? '',
      placeholder: '- Valitse -',
    }}
    value={value}
  />
);

export const AddressSelect = (props: WidgetProps) => {
  const addresses = useAtomValue(getAddressesAtom);
  const options = Object.assign(addresses.map(({
    street
  }) => ({
    label: street,
    value: street,
  })));

  return (
    <SelectWidget {...{...props, options: {enumOptions: options}}} />
  )
};

export const BankAccountSelect = (props: WidgetProps) => {
  const accounts = useAtomValue(getAccountsAtom);
  const options = Object.assign(accounts.map(({
    bankAccount
  }) => ({
    label: bankAccount,
    value: bankAccount,
  })));

  return (
    <SelectWidget {...{...props, options: {enumOptions: options}}} />
  );
}

export const CommunityOfficialsSelect = (props: WidgetProps) => {
  const officials = useAtomValue(getOfficialsAtom);
  const options = Object.assign(officials.map(({
    name,
    official_id,
  }) => ({
    label: name,
    value: official_id,
  })));

  const selectProps: SelectWidgetProps = {
    ...props,
    options: {enumOptions: options},
  }

  if (!options.length) {
    selectProps.assistive = 'Profiiliisi ei ole tallennettu yhtään yhteisöstä vastaavaa henkilöä, joten et voi lisätä niitä hakemukselle.';
  }

  return (
    <SelectWidget {...{...selectProps}} />
  );
}

import { WidgetProps } from '@rjsf/utils';
import { TextArea as HDSTextArea, TextInput as HDSTextInput, Select  } from 'hds-react';
import { useAtomValue } from 'jotai';
import { ChangeEvent } from 'react';
import { getAccountsAtom, getAddressesAtom, getOfficialsAtom, shouldRenderPreviewAtom } from '../store';

/**
 * Transform raw errors to a more readable format.
 *
 * @param {array|undefned} rawErrors - Errors from RJSF form
 * @return {string} - Resulting error messagea
 */
export const formatErrors = (rawErrors: string[]|undefined) => {
  if (!rawErrors) {
    return undefined;
  }

  return rawErrors.join('\n');
};

export const PreviewInput = ({
  value,
  label,
  uiSchema
}: {
  value?: string;
  label?: string;
  uiSchema: any;
}) => (
  <>
    {/* @todo fix when rebuilding styles  */}
    {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
    {!uiSchema?.['ui:options']?.hideNameFromPrint && <label>{uiSchema?.['ui:options']?.printableName?.toString() ?? label}</label>}
    {value ?? '-'}
  </>
);

export const TextInput = ({
  id,
  label,
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  uiSchema,
  value,
}: WidgetProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  if (shouldRenderPreview) {
    return <PreviewInput value={value} label={label} uiSchema={uiSchema} />
  }

  return (
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
      tooltipButtonLabel={uiSchema?.['ui:options']?.tooltipButtonLabel?.toString()}
      tooltipLabel={uiSchema?.['ui:options']?.tooltipLabel?.toString()}
      tooltipText={uiSchema?.['ui:options']?.tooltipText?.toString()}
      value={value ?? ''}
    />
  );
}

export const TextArea = ({
  id,
  label,
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  value,
  uiSchema,
}: WidgetProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  if (shouldRenderPreview) {
    return <PreviewInput value={value} label={label} uiSchema={uiSchema} />
  }

  return <HDSTextArea
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
};

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
  uiSchema,
}: SelectWidgetProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  if (shouldRenderPreview) {
    return <PreviewInput value={value} label={label} uiSchema={uiSchema} />
  }

  return (
    <Select
      id={id}
      disabled={readonly}
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
};

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
    selectProps.assistive = Drupal.t(
      'You do not have any community officials saved in your profile, so you cannot add any to the application.',
      {},
      {context: 'Grants application: Community officials'}
    );
  }

  return (
    <SelectWidget {...{...selectProps}} />
  );
}

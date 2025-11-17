// biome-ignore-all lint/suspicious/noExplicitAny: @todo UHF-12501
// biome-ignore-all lint/a11y/noLabelWithoutControl: @todo UHF-12501
// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
import { type ChangeEvent, useCallback } from 'react';
import {
  Fieldset,
  TextArea as HDSTextArea,
  TextInput as HDSTextInput,
  Notification,
  RadioButton,
  Select,
} from 'hds-react';
import { useAtomCallback } from 'jotai/utils';
import { useAtomValue } from 'jotai';
import { useTranslation } from 'react-i18next';
import type { WidgetProps } from '@rjsf/utils';

import { defaultSelectTheme } from '@/react/common/constants/selectTheme';
import { defaultRadioButtonStyle } from '@/react/common/constants/radioButtonStyle';
import { formatErrors } from '../utils';
import {
  getAccountsAtom,
  getAddressesAtom,
  getOfficialsAtom,
  getProfileAtom,
  shouldRenderPreviewAtom,
} from '../store';

export const PreviewInput = ({
  value,
  label,
  uiSchema,
}: {
  value?: string;
  label?: string;
  uiSchema: any;
}) => (
  <>
    {/* @todo fix when rebuilding styles  */}
    {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
    {!uiSchema?.['ui:options']?.hideNameFromPrint && (
      <label>
        {uiSchema?.['ui:options']?.printableName?.toString() ?? label}
      </label>
    )}
    {Array.isArray(value) ? value.join(', ') : (value ?? '-')}
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
    return <PreviewInput value={value} label={label} uiSchema={uiSchema} />;
  }

  const getMaxWidth = () => {
    switch (uiSchema?.['misc:variant']) {
      case 'width-s':
        return 'var(--forms-app-element-width--input-small)';
      case 'width-m':
        return 'var(--forms-app-element-width--input-medium)';
      case 'width-l':
        return 'var(--forms-app-element-width--input-large)';
      case 'width-xl':
        return 'var(--forms-app-element-width--input-xl)';
      case 'width-xxl':
        return 'var(--forms-app-element-width--input-xxl)';
      default:
        return 'auto';
    }
  };

  return (
    <HDSTextInput
      errorText={formatErrors(rawErrors)}
      hideLabel={false}
      id={id}
      invalid={Boolean(rawErrors?.length)}
      label={label}
      name={name}
      onBlur={() => null}
      onChange={(event: ChangeEvent<HTMLInputElement>) =>
        onChange(event.target.value)
      }
      onFocus={() => null}
      readOnly={readonly}
      required={required}
      style={{ maxWidth: getMaxWidth() }}
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
  schema,
  value,
  uiSchema,
}: WidgetProps) => {
  const readGrantsProfile = useAtomCallback(
    useCallback((get) => get(getProfileAtom), []),
  );
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  if (shouldRenderPreview) {
    return <PreviewInput value={value} label={label} uiSchema={uiSchema} />;
  }

  const getDefaultValue = () => {
    if (!uiSchema?.['misc:profilePrefill']) {
      return;
    }
    const grantsProfile = readGrantsProfile();
    return grantsProfile?.[uiSchema?.['misc:profilePrefill']] ?? undefined;
  };

  const maxLength = uiSchema?.['misc:max-length'] ?? 5000;

  return (
    <HDSTextArea
      defaultValue={getDefaultValue()}
      errorText={formatErrors(rawErrors)}
      helperText={`${value?.length || 0}/${maxLength}`}
      hideLabel={false}
      invalid={Boolean(rawErrors?.length)}
      onBlur={() => null}
      onChange={(event: React.ChangeEvent<HTMLTextAreaElement>) =>
        onChange(event.target.value)
      }
      onFocus={() => null}
      readOnly={readonly}
      {...{ id, label, maxLength, name, required, value }}
    />
  );
};

type SelectWidgetProps = WidgetProps & { assistive?: string };

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
    return <PreviewInput value={value} label={label} uiSchema={uiSchema} />;
  }

  return (
    <Select
      id={id}
      disabled={readonly}
      invalid={Boolean(rawErrors?.length)}
      multiSelect={multiple}
      onBlur={() => null}
      onChange={(newValue) => {
        if (!newValue.length) {
          onChange(undefined);
          return;
        }
        if (multiple) {
          onChange(newValue.map((option) => option.value));
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
      theme={defaultSelectTheme}
      className='hdbt-form--select'
    />
  );
};

export const AddressSelect = (props: WidgetProps) => {
  const addresses = useAtomValue(getAddressesAtom);
  const options = Object.assign(
    addresses.map(({ street }) => ({ label: street, value: street })),
  );

  return <SelectWidget {...{ ...props, options: { enumOptions: options } }} />;
};

export const BankAccountSelect = (props: WidgetProps) => {
  const accounts = useAtomValue(getAccountsAtom);
  const options = Object.assign(
    accounts.map(({ bankAccount }) => ({
      label: bankAccount,
      value: bankAccount,
    })),
  );

  return <SelectWidget {...{ ...props, options: { enumOptions: options } }} />;
};

const roleMap = new Map([
  [1, Drupal.t('Chairperson', {}, { context: 'grants_profile' })],
  [2, Drupal.t('Contact person', {}, { context: 'grants_profile' })],
  [3, Drupal.t('Other', {}, { context: 'grants_profile' })],
  [4, Drupal.t('Treasurer', {}, { context: 'grants_profile' })],
  [5, Drupal.t('Auditor', {}, { context: 'grants_profile' })],
  [7, Drupal.t('Secretary', {}, { context: 'grants_profile' })],
  [8, Drupal.t('Deputy chairperson', {}, { context: 'grants_profile' })],
  [9, Drupal.t('Chief executive officer', {}, { context: 'grants_profile' })],
  [10, Drupal.t('Producer', {}, { context: 'grants_profile' })],
  [11, Drupal.t('Responsible person', {}, { context: 'grants_profile' })],
  [12, Drupal.t('Executive director', {}, { context: 'grants_profile' })],
]);

/**
 * Get translated string for community official role.
 *
 * @param {number} roleId - Role id
 *
 * @return {string|undefined} - Role name or undefined
 */
const getCommunityOfficialRole = (roleId: number | string) =>
  roleMap.get(Number(roleId));

export const CommunityOfficialsSelect = ({
  label,
  value,
  uiSchema,
  ...rest
}: WidgetProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const officials = useAtomValue(getOfficialsAtom);
  const options = Object.assign(
    officials.map(({ name, role, official_id }) => ({
      label: `${name} (${getCommunityOfficialRole(role)})`,
      value: official_id,
    })),
  );

  const formatPreviewValue = () => {
    if (Array.isArray(value)) {
      return value.map((official_id) => {
        const { email, name, phone, role } = officials.find(
          ({ official_id: officialId }) => officialId === official_id,
        );

        return `${getCommunityOfficialRole(role)}: ${name} (${email}, ${phone})`;
      });
    }

    const { email, name, phone, role } = officials.find(
      ({ official_id: officialId }) => officialId === value,
    );

    return `${getCommunityOfficialRole(role)}: ${name} (${email}, ${phone})`;
  };

  if (shouldRenderPreview) {
    return (
      <PreviewInput
        value={formatPreviewValue()}
        label={label}
        uiSchema={uiSchema}
      />
    );
  }

  const selectProps: SelectWidgetProps = {
    label,
    value,
    uiSchema,
    ...rest,
    options: { enumOptions: options },
  };

  if (!options.length) {
    selectProps.assistive = Drupal.t(
      'You do not have any community officials saved in your profile, so you cannot add any to the application.',
      {},
      { context: 'Grants application: Community officials' },
    );
  }

  return <SelectWidget {...{ ...selectProps }} />;
};

export const RadioWidget = ({
  id,
  label,
  onChange,
  options,
  rawErrors,
  required,
  value,
  uiSchema,
  ...rest
}: WidgetProps) => {
  const { t } = useTranslation();
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  if (shouldRenderPreview) {
    return <PreviewInput value={value} label={label} uiSchema={uiSchema} />;
  }

  const { affirmativeExpands } = uiSchema?.['ui:options'] ?? {};
  return (
    <>
      {affirmativeExpands && (
        <Notification
          label={t('affirmative_expands')}
          type='info'
          className='hdbt-form--notification'
        />
      )}
      <Fieldset
        heading={`${label}${required ? ' *' : ''}`}
        className='hdbt-form--fieldset'
      >
        {options?.enumOptions?.map((option: any) => {
          const optionId = `${id}_${option.value}`;

          return (
            <RadioButton
              checked={option.value === value}
              id={optionId}
              key={optionId}
              label={option.label}
              name={optionId}
              onChange={() => onChange(option.value)}
              style={defaultRadioButtonStyle}
              className='hdbt-form--radiobutton'
            />
          );
        })}
        {rawErrors?.length > 0 && (
          <Notification type='error' className='hdbt-form--notification'>
            {formatErrors(rawErrors)}
          </Notification>
        )}
      </Fieldset>
    </>
  );
};

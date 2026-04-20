import { type ChangeEvent, type FocusEvent, type WheelEvent, useCallback, useEffect } from 'react';
import {
  Checkbox,
  DateInput,
  Fieldset,
  TextArea as HDSTextArea,
  TextInput as HDSTextInput,
  Notification,
  NumberInput,
  RadioButton,
  Select,
} from 'hds-react';
import { DateTime } from 'luxon';
import { useAtomCallback } from 'jotai/utils';
import { useAtomValue } from 'jotai';
import { useTranslation } from 'react-i18next';
import type { RJSFSchema, UiSchema, WidgetProps } from '@rjsf/utils';

import { defaultSelectTheme } from '@/react/common/constants/selectTheme';
import { defaultRadioButtonStyle } from '@/react/common/constants/radioButtonStyle';
import { formatErrors, getTooltip } from '../utils';
import {
  getAccountsAtom,
  getAddressesAtom,
  getOfficialsAtom,
  getProfileAtom,
  isReadOnlyAtom,
  shouldRenderPreviewAtom,
} from '../store';
import { HDS_DATE_FORMAT } from '@/react/common/enum/HDSDateFormat';

export const PreviewInput = ({
  value,
  label,
  uiSchema,
}: {
  value?: string | string[];
  label?: string;
  // biome-ignore lint/suspicious/noExplicitAny: This is the type that RJSF uses
  uiSchema: UiSchema<any, RJSFSchema, any> | undefined;
}) => (
  <>
    {!uiSchema?.['ui:options']?.hideNameFromPrint && (uiSchema?.['ui:options']?.printableName || label) && (
      <span className='grants-form--preview-section__label'>
        {uiSchema?.['ui:options']?.printableName?.toString() ?? label}
      </span>
    )}
    {Array.isArray(value) ? value.join(', ') : (value ?? '-')}
  </>
);

const sanitizeNumericInput = (value: string, allowPhone = false): string => {
  const pattern = allowPhone ? /[^0-9 ,+()]/g : /[^0-9 ,]/g;
  return value.replace(pattern, '').replace(/ {2,}/g, ' ');
};

export const TextInput = ({
  id,
  label,
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  schema,
  uiSchema,
  value,
}: WidgetProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const isNumberInput = schema.type === 'number' || schema.type === 'integer';
  const phone = uiSchema?.['misc:phone'] ?? false;

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

  if (isNumberInput) {
    return (
      <NumberInput
        disabled={readonly}
        errorText={formatErrors(rawErrors)}
        hideLabel={false}
        id={id}
        invalid={Boolean(rawErrors?.length)}
        label={label}
        min={0}
        name={name}
        onBlur={() => null}
        onChange={(event: ChangeEvent<HTMLInputElement>) => {
          const sanitized = sanitizeNumericInput(event.target.value);
          onChange(sanitized === '' ? undefined : sanitized);
        }}
        onFocus={(event: FocusEvent<HTMLInputElement>) => {
          if (event.target.value === '0') {
            event.target.select();
          }
        }}
        onWheel={(event: WheelEvent<HTMLInputElement>) => {
          event.currentTarget.blur();
        }}
        required={required}
        style={{ maxWidth: getMaxWidth() }}
        tooltip={getTooltip(uiSchema)}
        value={value ?? ''}
      />
    );
  }

  return (
    <HDSTextInput
      errorText={formatErrors(rawErrors)}
      disabled={readonly}
      hideLabel={false}
      id={id}
      invalid={Boolean(rawErrors?.length)}
      label={label}
      name={name}
      onBlur={() => null}
      onChange={(event: ChangeEvent<HTMLInputElement>) => {
        const value = phone ? sanitizeNumericInput(event.target.value, true) : event.target.value;
        onChange(value === '' ? undefined : value);
      }}
      onFocus={() => null}
      required={required}
      style={{ maxWidth: getMaxWidth() }}
      tooltip={getTooltip(uiSchema)}
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
  uiSchema,
  schema,
}: WidgetProps) => {
  const readGrantsProfile = useAtomCallback(useCallback((get) => get(getProfileAtom), []));
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  const getDefaultValue = () => {
    if (!uiSchema?.['misc:profilePrefill']) {
      return;
    }
    const grantsProfile = readGrantsProfile();
    return grantsProfile?.[uiSchema?.['misc:profilePrefill'] as keyof typeof grantsProfile] ?? undefined;
  };

  const defaultValue = getDefaultValue();
  const maxLength = uiSchema?.['misc:max-length'] ?? 5000;

  useEffect(() => {
    if (!value && defaultValue) {
      onChange(defaultValue);
    }
  }, [value, defaultValue, onChange]);

  if (shouldRenderPreview) {
    return <PreviewInput value={value} label={label} uiSchema={uiSchema} />;
  }

  return (
    <>
      {schema.description && <div className='hdbt-form--description'>{schema.description}</div>}
      <HDSTextArea
        disabled={readonly}
        errorText={formatErrors(rawErrors)}
        helperText={`${value?.length || 0}/${maxLength}`}
        hideLabel={false}
        invalid={Boolean(rawErrors?.length)}
        onBlur={() => null}
        onChange={(event: React.ChangeEvent<HTMLTextAreaElement>) => {
          const val = event.target.value;
          onChange(val === '' ? undefined : val);
        }}
        onFocus={() => null}
        tooltip={getTooltip(uiSchema)}
        value={value ?? ''}
        {...{ id, label, maxLength, name, required }}
      />
    </>
  );
};

type SelectWidgetProps = WidgetProps & { assistive?: string };

export const SelectWidget = ({
  assistive,
  id,
  label,
  multiple,
  onChange,
  options,
  rawErrors,
  readonly,
  required,
  value,
  uiSchema,
}: SelectWidgetProps) => {
  const { t } = useTranslation();
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  if (shouldRenderPreview) {
    return <PreviewInput value={value} label={label} uiSchema={uiSchema} />;
  }

  return (
    <Select
      className='hdbt-form--select'
      disabled={readonly}
      id={id}
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
        placeholder: t('select.placeholder'),
      }}
      theme={defaultSelectTheme}
      tooltip={getTooltip(uiSchema)}
      value={value}
    />
  );
};

export const AddressSelect = (props: WidgetProps) => {
  const addresses = useAtomValue(getAddressesAtom);
  const options = addresses?.length
    ? Object.assign(addresses.map(({ street }) => ({ label: street, value: street })))
    : [];

  return <SelectWidget {...{ ...props, options: { enumOptions: options } }} />;
};

export const BankAccountSelect = (props: WidgetProps) => {
  const accounts = useAtomValue(getAccountsAtom);
  const options = accounts?.length
    ? Object.assign(accounts.map(({ bankAccount }) => ({ label: bankAccount, value: bankAccount })))
    : [];

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
const getCommunityOfficialRole = (roleId: number | string) => roleMap.get(Number(roleId));

export const CommunityOfficialsSelect = ({ label, value, uiSchema, ...rest }: WidgetProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const officials = useAtomValue(getOfficialsAtom);
  const options = officials?.length
    ? Object.assign(
        officials.map(({ name, role, official_id }) => ({
          label: `${name} (${getCommunityOfficialRole(role)})`,
          value: official_id,
        })),
      )
    : [];

  const formatPreviewValue = () => {
    if (Array.isArray(value)) {
      return value.map((official_id) => {
        const { email, name, phone, role } = officials.find(
          ({ official_id: officialId }) => officialId === official_id,
        );

        return `${getCommunityOfficialRole(role)}: ${name} (${email}, ${phone})`;
      });
    }

    const { email, name, phone, role } = officials.find(({ official_id: officialId }) => officialId === value);

    return `${getCommunityOfficialRole(role)}: ${name} (${email}, ${phone})`;
  };

  if (shouldRenderPreview) {
    return <PreviewInput value={formatPreviewValue()} label={label} uiSchema={uiSchema} />;
  }

  const selectProps: SelectWidgetProps = { label, value, uiSchema, ...rest, options: { enumOptions: options } };

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
  schema,
}: WidgetProps) => {
  const { t } = useTranslation();
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const isReadOnly = useAtomValue(isReadOnlyAtom);

  if (shouldRenderPreview) {
    const selectedLabel = options?.enumOptions?.find((opt) => opt.value === value)?.label ?? value;
    return <PreviewInput value={selectedLabel} label={label} uiSchema={uiSchema} />;
  }

  const { affirmativeExpands } = uiSchema?.['ui:options'] ?? {};
  return (
    <>
      {affirmativeExpands && (
        <Notification label={t('affirmative_expands')} type='info' className='hdbt-form--notification' />
      )}
      <Fieldset
        heading={`${label}${required ? ' *' : ''}`}
        className='hdbt-form--fieldset'
        tooltip={getTooltip(uiSchema)}
      >
        {schema.description && <div className='hdbt-form--description'>{schema.description}</div>}
        {options?.enumOptions?.map((option) => {
          const optionId = `${id}_${option.value}`;

          return (
            <RadioButton
              checked={option.value === value}
              disabled={isReadOnly}
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
        {!!rawErrors?.length && (
          <Notification type='error' className='hdbt-form--notification'>
            {formatErrors(rawErrors)}
          </Notification>
        )}
      </Fieldset>
    </>
  );
};

export const DateWidget = ({ id, label, onChange, rawErrors, required, uiSchema, value }: WidgetProps) => {
  const { currentLanguage } = drupalSettings.path;
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const isReadOnly = useAtomValue(isReadOnlyAtom);

  let date: DateTime | undefined;
  const handleChange = (_dateStr: string, dateObject: Date) => {
    try {
      date = DateTime.fromJSDate(dateObject);
    } catch (_error) {
      return;
    }

    onChange(date?.toISODate());
  };

  let formattedValue: string | undefined;
  try {
    formattedValue = value ? DateTime.fromISO(value).toFormat(HDS_DATE_FORMAT) : undefined;
  } catch (_error) {
    formattedValue = undefined;
  }

  if (shouldRenderPreview) {
    return <PreviewInput value={formattedValue} label={label} uiSchema={uiSchema} />;
  }

  return (
    <DateInput
      disabled={isReadOnly}
      errorText={formatErrors(rawErrors)}
      invalid={Boolean(rawErrors?.length)}
      language={currentLanguage}
      onChange={handleChange}
      tooltip={getTooltip(uiSchema)}
      value={formattedValue}
      {...{
        id,
        label,
        required,
      }}
    />
  );
};

export const CheckboxWidget = ({ id, label, onChange, value, uiSchema }: WidgetProps) => {
  const { t } = useTranslation();
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const isReadOnly = useAtomValue(isReadOnlyAtom);

  if (shouldRenderPreview) {
    return <PreviewInput value={value ? t('Yes') : t('No')} label={label} uiSchema={uiSchema} />;
  }

  return (
    <Checkbox
      disabled={isReadOnly}
      id={id}
      label={label ?? ''}
      checked={Boolean(value)}
      onChange={(event: ChangeEvent<HTMLInputElement>) => onChange(event.target.checked)}
    />
  );
};

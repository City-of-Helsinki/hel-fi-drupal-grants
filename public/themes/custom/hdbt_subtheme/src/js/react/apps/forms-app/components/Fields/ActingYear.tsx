import { Select } from 'hds-react';
import { useAtomValue } from 'jotai';
import { getActingYearsAtom, isReadOnlyAtom, shouldRenderPreviewAtom } from '../../store';
import type { FieldProps } from '@rjsf/utils';
import { defaultSelectTheme } from '@/react/common/constants/selectTheme';
import { PreviewInput } from '../Input';
import { formatErrors } from '../../utils';
import type OptionType from '@/common/types/OptionType';
import { useTranslation } from 'react-i18next';

export const ActingYear = ({ formData, idSchema, name, onChange, rawErrors, schema, uiSchema }: FieldProps) => {
  const { t } = useTranslation();
  const isReadOnly = useAtomValue(isReadOnlyAtom);
  const yearOptions = useAtomValue(getActingYearsAtom);
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const label = schema?.title ?? undefined;

  if (shouldRenderPreview) {
    return <PreviewInput label={label} value={formData?.[0]?.label} uiSchema={uiSchema} />;
  }

  const options = (yearOptions ?? []).map((year) => {
    const value = year.toString();
    return { label: value, value };
  });
  const matchedOption = options.find((option) => option.value === formData);
  const value = matchedOption ? [matchedOption] : [];

  const handleChange = (value?: OptionType[]) => {
    const extractedValue = value && value.length > 0 ? value[0].value : undefined;
    onChange(extractedValue);
  };

  return (
    <Select
      className='hdbt-form--select'
      disabled={isReadOnly}
      id={idSchema.$id}
      invalid={Boolean(rawErrors?.length)}
      name={name}
      onChange={handleChange}
      options={options}
      texts={{
        error: rawErrors ? formatErrors(rawErrors) : undefined,
        label: label ?? '',
        placeholder: t('select.placeholder'),
      }}
      theme={defaultSelectTheme}
      value={value}
    />
  );
};

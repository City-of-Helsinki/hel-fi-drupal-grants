import type { FieldProps } from '@rjsf/utils';
import { Select } from 'hds-react';
import { useAtomValue } from 'jotai';
import { useTranslation } from 'react-i18next';
import type OptionType from '@/common/types/OptionType';
import { defaultSelectTheme } from '@/react/common/constants/selectTheme';
import { getActingYearsAtom, isReadOnlyAtom, shouldRenderPreviewAtom } from '../../store';
import { formatErrors } from '../../utils';
import { PreviewInput } from '../Input';

export const ActingYear = ({
  formData,
  idSchema,
  name,
  onChange,
  rawErrors,
  required,
  schema,
  uiSchema,
}: FieldProps) => {
  const { t } = useTranslation();
  const isReadOnly = useAtomValue(isReadOnlyAtom);
  const yearOptions = useAtomValue(getActingYearsAtom);
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const label = schema?.title ?? undefined;

  if (shouldRenderPreview) {
    return <PreviewInput label={label} value={formData ?? undefined} uiSchema={uiSchema} />;
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
      required={required}
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

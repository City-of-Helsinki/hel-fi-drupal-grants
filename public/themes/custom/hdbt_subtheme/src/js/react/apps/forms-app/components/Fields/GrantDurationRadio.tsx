import type { FieldProps } from '@rjsf/utils';
import { Fieldset, RadioButton } from 'hds-react';
import { useAtomValue } from 'jotai';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

import {
  formDataAtomRef,
  getSubventionFieldsAtom,
  isEmptyPreviewAtom,
  isReadOnlyAtom,
  shouldRenderPreviewAtom,
} from '../../store';
import { getSubventionSum, ALLOWED_HTML_TAGS } from '../../utils';
import { htmlToReact } from '@/react/common/helpers/htmlToReact';
import { defaultRadioButtonStyle } from '@/react/common/constants/radioButtonStyle';
import { PreviewInput } from '../Input';

const MEDIUM_MIN = 20000;
const LARGE_MIN = 50000;

// Returns enum values allowed for the given subvention amount tier.
const getAllowedValues = (amount: number): string[] => {
  if (amount >= LARGE_MIN) return ['2', '3'];
  if (amount >= MEDIUM_MIN) return ['1', '2', '3'];
  return ['1'];
};

export const GrantDurationRadio = ({ formData, idSchema, onChange, required, schema, uiSchema }: FieldProps) => {
  const { t } = useTranslation();
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const isReadOnly = useAtomValue(isReadOnlyAtom);
  const isEmptyPreview = useAtomValue(isEmptyPreviewAtom);
  const formDataAtom = useAtomValue(formDataAtomRef);
  const rootFormData = useAtomValue(formDataAtom);
  const subventionFields = useAtomValue(getSubventionFieldsAtom);

  const sumStr = getSubventionSum(
    rootFormData,
    subventionFields.map((f) => `.${f}`),
  );
  const amount = Number(sumStr.replace(',', '.'));

  const enumValues = (schema.enum as string[]) ?? [];
  const enumNames = (uiSchema?.['ui:enumNames'] as string[]) ?? enumValues;
  const allOptions = enumValues.map((val, i) => ({ value: val, label: enumNames[i] ?? val }));

  const options = uiSchema?.['ui:options'] as Record<string, string> | undefined;
  const allowedValues = getAllowedValues(amount);
  const availableOptions = allOptions.filter((o) => allowedValues.includes(o.value));

  const descriptionKey =
    amount >= LARGE_MIN
      ? options?.largeDescription
      : amount >= MEDIUM_MIN
        ? options?.mediumDescription
        : options?.smallDescription;

  // Auto-correct value when current selection is no longer in the allowed tier.
  useEffect(() => {
    if (formData != null && !getAllowedValues(amount).includes(formData as string)) {
      onChange(getAllowedValues(amount)[0]);
    }
  }, [amount, formData, onChange]);

  if (shouldRenderPreview) {
    const selectedLabel = allOptions.find((o) => o.value === formData)?.label ?? (formData as string);
    return <PreviewInput value={selectedLabel} label={schema.title} uiSchema={uiSchema} />;
  }

  const id = idSchema.$id;
  const description = descriptionKey ? t(descriptionKey) : undefined;

  return (
    <Fieldset heading={`${schema.title ?? ''}${required ? ' *' : ''}`} className='hdbt-form--fieldset'>
      {description && <div className='hdbt-form--description'>{htmlToReact(description, ALLOWED_HTML_TAGS)}</div>}
      {availableOptions.map((option) => {
        const optionId = `${id}_${option.value}`;
        return (
          <RadioButton
            checked={option.value === (formData as string)}
            className='hdbt-form--radiobutton'
            disabled={isReadOnly && !isEmptyPreview}
            id={optionId}
            key={optionId}
            label={option.label}
            name={optionId}
            onChange={() => onChange(option.value)}
            style={defaultRadioButtonStyle}
          />
        );
      })}
    </Fieldset>
  );
};

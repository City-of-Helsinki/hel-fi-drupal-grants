import type { FieldProps } from '@rjsf/utils';
import { TextInput } from 'hds-react';
import { useAtomValue } from 'jotai';
import { useEffect, useRef, type ComponentPropsWithRef } from 'react';

import { formDataAtomRef, shouldRenderPreviewAtom } from '../../store';
import { getNestedSchemaProperty, getSubventionSum, sanitizeNumericInput } from '../../utils';
import { PreviewInput } from '../Input';

type SourceShape = 'scalar' | 'subvention';

const sumScalarFields = (formData: unknown, sourceFields: string[]): string => {
  const total = sourceFields.reduce((acc, field) => {
    const raw = getNestedSchemaProperty(formData as never, `.${field}`);
    const numeric = Number(String(raw ?? '0').replace(',', '.'));
    return Number.isNaN(numeric) ? acc : acc + numeric;
  }, 0);

  return total.toString().replace('.', ',');
};

export const ComputedSum = ({ idSchema, name, onChange, schema, uiSchema }: FieldProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const formDataAtom = useAtomValue(formDataAtomRef);
  const data = useAtomValue(formDataAtom);

  const sourceFields = (uiSchema?.['ui:options']?.sourceFields as string[] | undefined) ?? [];
  const sourceShape = (uiSchema?.['ui:options']?.sourceShape as SourceShape | undefined) ?? 'scalar';

  if (!sourceFields.length) {
    console.error(`ComputedSum field "${name}" is missing ui:options.sourceFields`);
  }

  const sum =
    sourceShape === 'subvention'
      ? getSubventionSum(
          data,
          sourceFields.map((field) => `.${field}`),
        )
      : sumScalarFields(data, sourceFields);

  const prevSumRef = useRef<string>('0');
  useEffect(() => {
    if (prevSumRef.current !== sum) {
      prevSumRef.current = sum;
      onChange(sum);
    }
  }, [sum, onChange]);

  const formattedSum = sanitizeNumericInput(sum.toString(), 'decimal-number');

  if (shouldRenderPreview) {
    return <PreviewInput label={schema?.title} value={formattedSum} uiSchema={uiSchema} />;
  }

  return (
    <TextInput
      {...({
        disabled: true,
        id: idSchema.$id,
        label: schema?.title,
        name,
        value: formattedSum,
      } as ComponentPropsWithRef<typeof TextInput>)}
    />
  );
};

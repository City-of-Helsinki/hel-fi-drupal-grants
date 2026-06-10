import type { FieldProps } from '@rjsf/utils';
import { TextInput } from 'hds-react';
import { useAtomValue } from 'jotai';
import { useEffect, type ComponentPropsWithRef } from 'react';

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

export const ComputedSum = ({ formData, idSchema, name, onChange, schema, uiSchema }: FieldProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const formDataAtom = useAtomValue(formDataAtomRef);
  const data = useAtomValue(formDataAtom);

  const sourceFields = (uiSchema?.['ui:options']?.sourceFields as string[] | undefined) ?? [];
  const sourceShape = (uiSchema?.['ui:options']?.sourceShape as SourceShape | undefined) ?? 'scalar';
  const numericOutput = (uiSchema?.['ui:options']?.numericOutput as boolean | undefined) ?? false;
  const hidden = (uiSchema?.['ui:options']?.hidden as boolean | undefined) ?? false;
  const subventionType = uiSchema?.['ui:options']?.subventionType as string | undefined;

  if (!sourceFields.length) {
    console.error(`ComputedSum field "${name}" is missing ui:options.sourceFields`);
  }

  const sum =
    sourceShape === 'subvention'
      ? getSubventionSum(
          data,
          sourceFields.map((field) => `.${field}`),
          subventionType,
        )
      : sumScalarFields(data, sourceFields);

  const valueToWrite = numericOutput ? Number(sum.replace(',', '.')) : sum;

  // Sync the computed value into form data whenever it diverges from the stored
  // value. Comparing against the stored value (rather than a mount-local ref)
  // ensures the value is corrected back down — e.g. when the source field is
  // cleared after the dependent step was already visited — so conditional gates
  // driven by this sum re-close instead of sticking on a stale value.
  useEffect(() => {
    if (formData !== valueToWrite) {
      onChange(valueToWrite);
    }
  }, [formData, valueToWrite, onChange]);

  if (hidden) {
    return null;
  }

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

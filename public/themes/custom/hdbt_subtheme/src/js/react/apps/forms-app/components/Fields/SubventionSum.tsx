import type { FieldProps } from '@rjsf/utils';
import { TextInput } from 'hds-react';
import { useAtomValue } from 'jotai';
import { useEffect, useRef, type ComponentPropsWithRef } from 'react';

import { formDataAtomRef, getSubventionFieldsAtom, shouldRenderPreviewAtom } from '../../store';
import { getSubventionSum, sanitizeNumericInput } from '../../utils';
import { PreviewInput } from '../Input';

export const SubventionSum = ({ idSchema, name, onChange, schema, uiSchema }: FieldProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const fields = useAtomValue(getSubventionFieldsAtom);
  const formDataAtom = useAtomValue(formDataAtomRef);
  const data = useAtomValue(formDataAtom);

  const sum = getSubventionSum(
    data,
    fields.map((field) => `.${field}`),
  );

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

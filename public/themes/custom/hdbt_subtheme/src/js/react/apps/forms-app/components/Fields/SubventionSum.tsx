// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
import type { FieldProps } from '@rjsf/utils';
import { TextInput } from 'hds-react';
import { useAtomValue } from 'jotai';
import { useEffect, useRef } from 'react';

import { formDataAtomRef, getSubventionFieldsAtom, shouldRenderPreviewAtom } from '../../store';
import { getSubventionSum } from '../../utils';
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

  const prevSumRef = useRef<number | null>(null);
  useEffect(() => {
    if (prevSumRef.current !== sum) {
      prevSumRef.current = sum;
      onChange(sum);
    }
  }, [sum, onChange]);

  if (shouldRenderPreview) {
    return <PreviewInput label={schema?.title} value={sum.toString()} uiSchema={uiSchema} />;
  }

  return <TextInput disabled id={idSchema.$id} label={schema?.title} name={name} value={sum.toString()} />;
};

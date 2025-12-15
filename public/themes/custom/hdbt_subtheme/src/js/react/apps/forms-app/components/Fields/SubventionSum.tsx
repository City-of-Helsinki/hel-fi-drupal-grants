// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
import type { FieldProps } from '@rjsf/utils';
import { TextInput } from 'hds-react';
import { useAtomValue } from 'jotai';

import { formDataAtomRef, getSubventionFieldsAtom } from '../../store';
import { getSubventionSum } from '../../utils';

export const SubventionSum = ({ idSchema, name, schema, ...rest }: FieldProps) => {
  const fields = useAtomValue(getSubventionFieldsAtom);
  const formDataAtom = useAtomValue(formDataAtomRef);
  const data = useAtomValue(formDataAtom);

  const sum = getSubventionSum(
    data,
    fields.map((field) => `.${field}`),
  );

  return <TextInput disabled id={idSchema.$id} label={schema?.title} value={sum} name={name} />;
};

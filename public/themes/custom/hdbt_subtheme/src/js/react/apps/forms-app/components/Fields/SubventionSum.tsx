import { FieldProps } from '@rjsf/utils';
import { TextInput } from 'hds-react';
import { useAtomValue } from 'jotai';

import { formDataAtomRef, getSubventionFieldsAtom } from '../../store';
import { getSubventionSum } from '../../utils';

export const SubventionSum = ({
  id,
  name,
}: FieldProps) => {
  const fields = useAtomValue(getSubventionFieldsAtom);
  const formDataAtom = useAtomValue(formDataAtomRef);
  const data = useAtomValue(formDataAtom);

  const sum = getSubventionSum(data, fields.map(field => `.${field}`));

  return <TextInput
    disabled
    label={name}
    value={sum}
    {...{
      id, 
      name,
    }}
  />;
};

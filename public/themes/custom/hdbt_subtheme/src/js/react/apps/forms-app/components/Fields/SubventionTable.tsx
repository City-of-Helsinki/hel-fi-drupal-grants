// biome-ignore-all lint/a11y/noLabelWithoutControl: @todo UHF-12501
// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
import type { FieldProps } from '@rjsf/utils';
import { useAtomValue } from 'jotai';
import { Notification, NumberInput, Fieldset } from 'hds-react';

import { shouldRenderPreviewAtom } from '../../store';
import { formatErrors } from '../../utils';

// Static values for Avus2 integration.
const AMOUNT_ID = 'amount';
const AMOUNT_LABEL = 'Euroa';
const AMOUNT_VALUE_TYPE = 'double';

const SUBVENTION_ID = 'subventionType';
const SUBVENTION_LABEL = 'Avustuslaji';
const SUBVENTION_VALUE_TYPE = 'string';

export const SubventionTable = ({ idSchema, formData, onChange, rawErrors, required, schema }: FieldProps) => {
  const id = idSchema.$id;
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  if (!schema.options || !schema.options.length) {
    console.error('Tried to render subvention table without items');
    return null;
  }

  const findIndexForData = (elementId: string, data = formData) =>
    data.findIndex((item) => item && item?.[0]?.value === elementId);

  if (shouldRenderPreview) {
    return (
      <ul>
        {schema.options.map(({ label, id: elementId }) => (
          <li key={elementId} style={{ listStyle: 'none' }}>
            <dt>{label}</dt>
            <dd>{formData?.[findIndexForData(elementId.toString())]?.[1].value || '-'}</dd>
          </li>
        ))}
      </ul>
    );
  }

  const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const { dataset, value } = event.target;
    const subventionId = dataset.subventionId as string;
    const data = formData && Array.isArray(formData) ? [...formData] : [];

    const newValue = [
      {
        ID: SUBVENTION_ID,
        label: SUBVENTION_LABEL,
        value: subventionId,
        valueType: SUBVENTION_VALUE_TYPE,
      },
      {
        ID: AMOUNT_ID,
        label: AMOUNT_LABEL,
        value,
        valueType: AMOUNT_VALUE_TYPE,
      },
    ];

    const index = findIndexForData(subventionId, data);

    if (index === -1) {
      data.push(newValue);
    } else {
      data[index] = newValue;
    }

    onChange(data);
  };

  const keyedData = {};
  if (Array.isArray(formData)) {
    formData.forEach((item) => {
      keyedData[item[0].value] = item[1].value;
    });
  }

  return (
    <>
      <div className='array-item'>
        <Fieldset className='hdbt-form--fieldset hdbt-form--fieldset--border' heading={`${schema.title}`}>
          {schema.options.map((item, i) => {
            const { id: itemId, label } = item;
            const key = `${id}-${itemId}`;

            return (
              <NumberInput
                data-subvention-id={itemId}
                defaultValue={0}
                id={key}
                key={key}
                label={label}
                min={0}
                onChange={handleChange}
                required={required}
                unit='€'
                value={keyedData[itemId] || ''}
              />
            );
          })}
        </Fieldset>
      </div>
      {rawErrors?.length > 0 && <Notification type='error'>{formatErrors(rawErrors)}</Notification>}
    </>
  );
};

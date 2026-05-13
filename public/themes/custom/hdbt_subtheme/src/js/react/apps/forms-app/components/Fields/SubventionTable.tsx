// biome-ignore-all lint/a11y/noLabelWithoutControl: @todo UHF-12501
// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
import type { FieldProps } from '@rjsf/utils';
import { useAtomValue } from 'jotai';
import type { ComponentPropsWithRef } from 'react';
import { Notification, TextInput, Fieldset } from 'hds-react';

import type { FocusEvent } from 'react';
import { isReadOnlyAtom, shouldRenderPreviewAtom } from '../../store';
import { useStartGrant } from '../../hooks/useStartGrant';
import { formatErrors, numberIsTooLarge, sanitizeNumericInput } from '../../utils';

type SubventionOption = { id: string; label: string };

type SubventionDataItem = [{ value: string }, { value: string }];

// Static values for Avus2 integration.
export const AMOUNT_ID = 'amount';
export const AMOUNT_LABEL = 'Euroa';
export const AMOUNT_VALUE_TYPE = 'double';

export const SUBVENTION_ID = 'subventionType';
export const SUBVENTION_LABEL = 'Avustuslaji';
export const SUBVENTION_VALUE_TYPE = 'string';

export const SubventionTable = ({
  idSchema,
  formData,
  onChange,
  rawErrors,
  required,
  schema,
  uiSchema,
}: FieldProps) => {
  const id = idSchema.$id;
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const isReadOnly = useAtomValue(isReadOnlyAtom);

  // Handle the liikunta_yleisavustushakemus start grant requirement.
  const startGrantSubventionId = useStartGrant(uiSchema, formData, onChange);

  if (!schema.options || !schema.options.length) {
    console.error('Tried to render subvention table without items');
    return null;
  }

  const findIndexForData = (elementId: string, data: SubventionDataItem[] = formData) =>
    data.findIndex((item: SubventionDataItem) => item && item?.[0]?.value === elementId);

  if (shouldRenderPreview) {
    return (
      <ul className='hdbt-react-form__subvention-table'>
        {(schema.options as SubventionOption[]).map(({ label, id: elementId }) => (
          <li key={elementId} className='hdbt-react-form__subvention-table__item'>
            <span className='grants-form--preview-section__label'>{label} (€)</span>
            {formData?.[findIndexForData(elementId.toString())]?.[1].value != null
              ? formData[findIndexForData(elementId.toString())][1].value
              : '-'}
          </li>
        ))}
      </ul>
    );
  }

  const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const { dataset, value } = event.target;

    if (numberIsTooLarge(value)) return;

    const subventionId = dataset.subventionId as string;
    const numericValue = sanitizeNumericInput(value, 'decimal-number');
    const data = formData && Array.isArray(formData) ? [...formData] : [];

    const newValue = [
      { ID: SUBVENTION_ID, label: SUBVENTION_LABEL, value: subventionId, valueType: SUBVENTION_VALUE_TYPE },
      { ID: AMOUNT_ID, label: AMOUNT_LABEL, value: numericValue, valueType: AMOUNT_VALUE_TYPE },
    ];

    const index = findIndexForData(subventionId, data);

    if (index === -1) {
      data.push(newValue);
    } else {
      data[index] = newValue;
    }

    onChange(data);
  };

  const keyedData: Record<string, string> = {};
  if (Array.isArray(formData)) {
    formData.forEach((item: SubventionDataItem) => {
      keyedData[item[0].value] = item[1].value;
    });
  }

  return (
    <>
      <div className='array-item'>
        <Fieldset className='hdbt-form--fieldset hdbt-form--fieldset--border' heading={`${schema.title}`}>
          {(schema.options as SubventionOption[]).map((item) => {
            const { id: itemId, label } = item;
            const key = `${id}-${itemId}`;

            return (
              <TextInput
                key={key}
                {...({
                  className: 'form-group field field-integer',
                  'data-subvention-id': itemId,
                  disabled: isReadOnly || itemId.toString() === startGrantSubventionId,
                  id: key,
                  inputMode: 'decimal',
                  label: `${label} (€)`,
                  onChange: handleChange,
                  onFocus: (event: FocusEvent<HTMLInputElement>) => {
                    if (event.target.value === '0') {
                      event.target.select();
                    }
                  },
                  value: keyedData[itemId] ?? '',
                } as unknown as Omit<ComponentPropsWithRef<typeof TextInput>, 'key'>)}
              />
            );
          })}
        </Fieldset>
      </div>
      {(rawErrors?.length ?? 0) > 0 && <Notification type='error'>{formatErrors(rawErrors)}</Notification>}
    </>
  );
};

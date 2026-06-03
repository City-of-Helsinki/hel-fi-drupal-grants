// biome-ignore-all lint/a11y/noLabelWithoutControl: @todo UHF-12501
// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
import type { FieldProps } from '@rjsf/utils';
import { Fieldset, Notification, TextInput } from 'hds-react';
import { useAtomValue } from 'jotai';
import type { ComponentPropsWithRef, FocusEvent } from 'react';
import { useEffect } from 'react';
import {
  AMOUNT_ID,
  AMOUNT_LABEL,
  AMOUNT_VALUE_TYPE,
  SUBVENTION_ID,
  SUBVENTION_LABEL,
  SUBVENTION_VALUE_TYPE,
} from '../../enum/SubventionFields';
import { useStartGrant } from '../../hooks/useStartGrant';
import { isReadOnlyAtom, shouldRenderPreviewAtom } from '../../store';
import { formatErrors, numberIsTooLarge, sanitizeNumericInput } from '../../utils';

type SubventionOption = { id: string; label: string };
type SubventionField = { ID: string; label: string; value: string; valueType: string };
type SubventionDataItem = [SubventionField, SubventionField];

/**
 * Create a default subvention data item.
 */
const createSubventionDataItem = (subventionId: string, amount: string): SubventionDataItem => [
  { ID: SUBVENTION_ID, label: SUBVENTION_LABEL, value: subventionId, valueType: SUBVENTION_VALUE_TYPE },
  { ID: AMOUNT_ID, label: AMOUNT_LABEL, value: amount, valueType: AMOUNT_VALUE_TYPE },
];

/**
 * Clear all subventions except the active one.
 */
const clearOtherSubventions = (data: SubventionDataItem[], activeSubventionId: string): SubventionDataItem[] =>
  data.map((item: SubventionDataItem) => {
    const itemId = item?.[0]?.value;
    if (itemId === activeSubventionId) return item;
    return [item[0], { ...item[1], value: '0' }];
  });

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
  const startGrant = useStartGrant(uiSchema);

  // When true, only one subvention can have a non-zero amount at a time.
  const useSingleSubvention = uiSchema?.['ui:options']?.useSingleSubvention === true;

  const findIndexForData = (elementId: string, data: SubventionDataItem[] = formData) =>
    data.findIndex((item: SubventionDataItem) => item && item?.[0]?.value === elementId);

  // Build the full sorted entry list on mount. The ATV mapping uses positional
  // indices, so all entries must exist and be in schema.options order from the start.
  useEffect(() => {
    const data = Array.isArray(formData) ? [...formData] : [];
    const optionOrder = Array.isArray(schema.options)
      ? (schema.options as SubventionOption[]).map(({ id: optionId }) => optionId.toString())
      : [];
    const sorted = optionOrder.map((optionId) => {
      const existing = data.find((item) => Array.isArray(item) && item[0]?.value === optionId);
      return existing ?? createSubventionDataItem(optionId, '0');
    });

    if (JSON.stringify(data) !== JSON.stringify(sorted)) {
      onChange(sorted);
    }
  }, []);

  // Handle the "start grant" value changes from sibling radio button.
  useEffect(() => {
    if (!startGrant.toggled || !startGrant.subventionId || !startGrant.valueWhenTrue) return;

    const data = Array.isArray(formData) ? [...formData] : [];
    const index = findIndexForData(startGrant.subventionId, data);
    const entry = createSubventionDataItem(
      startGrant.subventionId,
      startGrant.isApplied ? startGrant.valueWhenTrue : '0',
    );

    if (index === -1) {
      data.push(entry);
    } else {
      data[index] = entry;
    }

    if (useSingleSubvention && startGrant.isApplied) {
      onChange(clearOtherSubventions(data, startGrant.subventionId));
      return;
    }

    onChange(data);
  }, [startGrant.toggled, startGrant.isApplied, startGrant.subventionId, startGrant.valueWhenTrue]);

  if (!schema.options || !schema.options.length) {
    console.error('Tried to render subvention table without items');
    return null;
  }

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
    const nativeEvent: InputEvent = event.nativeEvent as InputEvent;
    const lastInput = nativeEvent.data ?? '';

    if (numberIsTooLarge(value)) return;

    const subventionId = dataset.subventionId as string;
    const numericValue = sanitizeNumericInput(value, 'decimal-number', lastInput);
    const data = formData && Array.isArray(formData) ? [...formData] : [];
    const newValue = createSubventionDataItem(subventionId, numericValue);
    const index = findIndexForData(subventionId, data);

    if (index === -1) {
      data.push(newValue);
    } else {
      data[index] = newValue;
    }

    // When useSingleSubvention is enabled and a value is entered,
    // clear all other subventions.
    if (useSingleSubvention && numericValue && numericValue !== '0') {
      onChange(clearOtherSubventions(data, subventionId));
    } else {
      onChange(data);
    }
  };

  const keyedData: Record<string, string> = {};
  if (Array.isArray(formData)) {
    formData.forEach((item: SubventionDataItem) => {
      keyedData[item[0].value] = item[1].value;
    });
  }

  // When useSingleSubvention is enabled, find the subvention that currently
  // has a real value (not 0) to disable all others.
  const activeSubventionId = useSingleSubvention
    ? Object.entries(keyedData).find(([, value]) => value !== '' && value !== '0')?.[0]
    : undefined;

  return (
    <>
      <div className='array-item'>
        <Fieldset className='hdbt-form--fieldset hdbt-form--fieldset--border' heading={`${schema.title} *`}>
          {(schema.options as SubventionOption[]).map((item) => {
            const { id: itemId, label } = item;
            const key = `${id}-${itemId}`;

            return (
              <TextInput
                key={key}
                {...({
                  className: 'form-group field field-integer',
                  'data-subvention-id': itemId,
                  disabled:
                    isReadOnly ||
                    itemId.toString() === startGrant.subventionId ||
                    (!!activeSubventionId && itemId.toString() !== activeSubventionId),
                  id: key,
                  inputMode: 'decimal',
                  label: `${label} (€)`,
                  onChange: handleChange,
                  onFocus: (event: FocusEvent<HTMLInputElement>) => {
                    if (event.target.value === '0') {
                      event.target.select();
                    }
                  },
                  value: keyedData[itemId] ?? '0',
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

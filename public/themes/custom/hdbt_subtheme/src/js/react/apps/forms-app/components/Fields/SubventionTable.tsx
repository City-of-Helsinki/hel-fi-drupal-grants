// biome-ignore-all lint/a11y/noLabelWithoutControl: @todo UHF-12501
// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
import type { FieldProps } from '@rjsf/utils';
import { useAtomValue } from 'jotai';
import { useTranslation } from 'react-i18next';
import { Notification, NumberInput } from 'hds-react';

import { shouldRenderPreviewAtom } from '../../store';
import { formatErrors } from '../../utils';

// Static values for Avus2 integration.
const AMOUNT_ID = 'amount';
const AMOUNT_LABEL = 'Euroa';
const AMOUNT_VALUE_TYPE = 'double';

const SUBVENTION_ID = 'subventionType';
const SUBVENTION_LABEL = 'Avustuslaji';
const SUBVENTION_VALUE_TYPE = 'string';

export const SubventionTable = ({
  id,
  formData,
  onChange,
  rawErrors,
  required,
  schema,
  uiSchema,
}: FieldProps) => {
  const { t } = useTranslation();
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  if (!schema.options || !schema.options.length) {
    console.error('Tried to render subvention table without items');
    return null;
  }

  const findIndexForData = (elementId, data = formData) =>
    data.findIndex((item) => item && item?.[0]?.value === elementId);

  if (shouldRenderPreview) {
    return (
      <ul>
        {schema.options.map(({ label, id: elementId }) => (
          <li key={elementId} style={{ listStyle: 'none' }}>
            <dt>{label}</dt>
            <dd>
              {formData?.[findIndexForData(elementId.toString())]?.[1].value ||
                '-'}
            </dd>
          </li>
        ))}
      </ul>
    );
  }

  const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const { id: elementId, value } = event.target;
    const data = formData && Array.isArray(formData) ? [...formData] : [];

    const newValue = [
      {
        ID: SUBVENTION_ID,
        label: SUBVENTION_LABEL,
        value: elementId,
        valueType: SUBVENTION_VALUE_TYPE,
      },
      {
        ID: AMOUNT_ID,
        label: AMOUNT_LABEL,
        value,
        valueType: AMOUNT_VALUE_TYPE,
      },
    ];

    const index = findIndexForData(elementId, data);

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
    <div className='table-layout-form webform-multiple-table'>
      {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
      <label className='js-form-required form-required'>
        {`${schema.title}`}
      </label>
      <table id='edit-subventions-items' className='responsive-enabled'>
        <thead>
          <tr>
            <th className='subventions-table--subventionTypeTitle webform-multiple-table--subventionTypeTitle'>
              {t('subvention.type')}
            </th>
            <th className='subventions-table--amount webform-multiple-table--amount'>
              {t('subvention.sum')}
            </th>
          </tr>
        </thead>
        <tbody>
          {schema.options.map((item, i) => {
            const { id: itemId, label } = item;
            const key = `${id}-${itemId}`;

            return (
              <tr key={key}>
                <td>
                  <div style={{ padding: 'var(--spacing-layout-2-xs)' }}>
                    {label}
                  </div>
                </td>
                <td>
                  <NumberInput
                    id={key}
                    onChange={handleChange}
                    min={0}
                    required={required}
                    style={{ '--border-width': 0, textAlign: 'right' }}
                    value={keyedData[key] || ''}
                  />
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
      {rawErrors?.length > 0 && (
        <Notification type='error'>{formatErrors(rawErrors)}</Notification>
      )}
    </div>
  );
};

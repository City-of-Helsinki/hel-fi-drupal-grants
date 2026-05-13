import type { UiSchema } from '@rjsf/utils';
import { useAtomValue } from 'jotai';
import { useEffect, useRef } from 'react';
import {
  AMOUNT_ID,
  AMOUNT_LABEL,
  AMOUNT_VALUE_TYPE,
  SUBVENTION_ID,
  SUBVENTION_LABEL,
  SUBVENTION_VALUE_TYPE,
} from '../components/Fields/SubventionTable';
import { getFormDataAtom } from '../store';

type StartGrantConfig = {
  controlledBy: string;
  subventionId: string;
  valueWhenTrue: string;
};

/**
 * Handle the start grant requirement.
 *
 * The form liikunta_yleisavustushakemus has an exception which needs
 * the subvention type "31" to be a fixed value or empty. The amount is controlled
 * by a sibling radio buttons (apply_for_start_grant).
 *
 * The behavior is configured via uiSchema's ui:options.
 * See usage: liikunta_yleisavustushakemus/uiSchema.json
 *
 * Return subventionId or undefined.
 */
export const useStartGrant = (
  uiSchema: UiSchema | undefined,
  formData: unknown[],
  onChange: (data: unknown) => void,
): string | undefined => {
  const fullFormData = useAtomValue(getFormDataAtom);
  const config = uiSchema?.['ui:options']?.startGrant as StartGrantConfig | undefined;

  // Track the previous radio button value to avoid calling onChange on every render.
  const prevValueRef = useRef<boolean | undefined>(undefined);

  // Read the apply_for_start_grant radio button value from the full form data.
  const isStartGrantApplied = config
    ? config.controlledBy
        .split('.')
        .reduce((acc: unknown, key) => (acc as Record<string, unknown>)?.[key], fullFormData) === true
    : undefined;

  useEffect(() => {
    // Skip if the radio button value has not changed since the last run.
    if (!config || isStartGrantApplied === prevValueRef.current) return;
    prevValueRef.current = isStartGrantApplied;

    const { subventionId, valueWhenTrue } = config;
    const data = Array.isArray(formData) ? [...formData] : [];
    const index = data.findIndex((item: unknown) => Array.isArray(item) && item[0]?.value === subventionId);

    // Build the subvention entry with the fixed amount when
    // the start grant is applied, otherwise clear the value.
    const entry = [
      { ID: SUBVENTION_ID, label: SUBVENTION_LABEL, value: subventionId, valueType: SUBVENTION_VALUE_TYPE },
      {
        ID: AMOUNT_ID,
        label: AMOUNT_LABEL,
        value: isStartGrantApplied ? valueWhenTrue : '',
        valueType: AMOUNT_VALUE_TYPE,
      },
    ];

    // Update the existing subvention entry or add a new one.
    if (index === -1) {
      data.push(entry);
    } else {
      data[index] = entry;
    }

    // When useSingleSubvention is enabled and start grant is applied, clear all other subventions.
    if (uiSchema?.['ui:options']?.useSingleSubvention === true && isStartGrantApplied) {
      onChange(
        data.map((item: unknown) => {
          if (!Array.isArray(item) || item[0]?.value === subventionId) return item;
          return [item[0], { ...item[1], value: '' }];
        }),
      );
    } else {
      onChange(data);
    }
  }, [isStartGrantApplied]);

  return config?.subventionId;
};

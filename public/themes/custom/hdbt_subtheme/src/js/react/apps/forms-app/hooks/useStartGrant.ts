import type { UiSchema } from '@rjsf/utils';
import { useAtomValue } from 'jotai';
import { useEffect, useRef } from 'react';
import { getFormDataAtom } from '../store';

type StartGrantConfig = {
  controlledBy: string;
  subventionId: string;
  valueWhenTrue: string;
};

type StartGrantState = {
  subventionId: string | undefined;
  valueWhenTrue: string | undefined;
  isApplied: boolean;
  toggled: boolean;
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
 * Returns StartGrantState.
 */
export const useStartGrant = (uiSchema: UiSchema | undefined): StartGrantState => {
  const fullFormData = useAtomValue(getFormDataAtom);
  const config = uiSchema?.['ui:options']?.startGrant as StartGrantConfig | undefined;

  // Read the apply_for_start_grant radio button value from the full form data.
  const isStartGrantApplied = config
    ? config.controlledBy
        .split('.')
        .reduce((acc: unknown, key) => (acc as Record<string, unknown>)?.[key], fullFormData) === true
    : false;

  // Initialize the ref to the current radio button value so the effect does not
  // fire on mount.
  const prevValueRef = useRef<boolean>(isStartGrantApplied);

  const toggled = !!config && isStartGrantApplied !== prevValueRef.current;

  useEffect(() => {
    prevValueRef.current = isStartGrantApplied;
  }, [isStartGrantApplied]);

  return {
    subventionId: config?.subventionId,
    valueWhenTrue: config?.valueWhenTrue,
    isApplied: isStartGrantApplied,
    toggled,
  };
};

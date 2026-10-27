import type { FieldProps } from '@rjsf/utils';
import { Notification } from 'hds-react';
import { useAtomValue } from 'jotai';
import { getSumMatchRulesAtom, shouldRenderPreviewAtom, sumMatchStatesAtom } from '../../store';
import { pathToElementId } from '../../utils';

/**
 * Shows the result of the `misc:sum-match` rule declared on this field.
 *
 * The notification only changes when the rule is validated (leaving one of
 * the compared groups, or moving to the next step), so the live region
 * announces the result then and not on every keystroke. Validation errors of
 * the rule are attached to this field, and the wrapper is focused on error.
 */
export const SumMatchStatus = ({ idSchema }: FieldProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const rules = useAtomValue(getSumMatchRulesAtom);
  const states = useAtomValue(sumMatchStatesAtom);
  const rule = rules.find(({ path }) => pathToElementId(path) === idSchema.$id);
  const state = rule ? states[rule.path] : undefined;

  if (!rule) {
    console.error(`sumMatchStatus field "${idSchema.$id}" has no misc:sum-match rule`);
  }

  if (shouldRenderPreview) {
    return null;
  }

  return (
    // biome-ignore lint/a11y/useSemanticElements: role="status" is announced more consistently than <output>.
    <div className='hdbt-form--sum-match-status' data-focus-on-error id={idSchema.$id} role='status' tabIndex={-1}>
      {state && (
        <Notification className='hdbt-form--notification' type={state.status === 'match' ? 'success' : 'error'}>
          {state.message}
        </Notification>
      )}
    </div>
  );
};

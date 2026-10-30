import type { FieldProps } from '@rjsf/utils';
import { TextInput } from 'hds-react';
import { useAtomValue } from 'jotai';
import { type ComponentPropsWithRef, useEffect } from 'react';

import {
  formDataAtomRef,
  getSumMatchRulesAtom,
  liveFormDataAtom,
  shouldRenderPreviewAtom,
  sumMatchStatesAtom,
} from '../../store';
import { findSumMatchRule, formatErrors, getSubventionSum, sanitizeNumericInput, sumFieldValues } from '../../utils';
import { PreviewInput } from '../Input';

type SourceShape = 'scalar' | 'subvention';

export const ComputedSum = ({ formData, idSchema, name, onChange, rawErrors, schema, uiSchema }: FieldProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const formDataAtom = useAtomValue(formDataAtomRef);
  const persistedData = useAtomValue(formDataAtom);
  const liveData = useAtomValue(liveFormDataAtom);
  const data = liveData ?? persistedData;
  const sumMatchRules = useAtomValue(getSumMatchRulesAtom);
  const sumMatchStates = useAtomValue(sumMatchStatesAtom);

  const sourceFields = (uiSchema?.['ui:options']?.sourceFields as string[] | undefined) ?? [];
  const sourceShape = (uiSchema?.['ui:options']?.sourceShape as SourceShape | undefined) ?? 'scalar';
  const numericOutput = (uiSchema?.['ui:options']?.numericOutput as boolean | undefined) ?? false;
  const hidden = (uiSchema?.['ui:options']?.hidden as boolean | undefined) ?? false;
  const subventionType = uiSchema?.['ui:options']?.subventionType as string | undefined;

  if (!sourceFields.length) {
    console.error(`ComputedSum field "${name}" is missing ui:options.sourceFields`);
  }

  const sum =
    sourceShape === 'subvention'
      ? getSubventionSum(
          data,
          sourceFields.map((field) => `.${field}`),
          subventionType,
        )
      : sumFieldValues(data, sourceFields).toString().replace('.', ',');

  const valueToWrite = numericOutput ? Number(sum.replace(',', '.')) : sum;

  // Sync the computed value into form data whenever it diverges from the stored
  // value. Comparing against the stored value (rather than a mount-local ref)
  // ensures the value is corrected back down — e.g. when the source field is
  // cleared after the dependent step was already visited — so conditional gates
  // driven by this sum re-close instead of sticking on a stale value.
  useEffect(() => {
    if (formData !== valueToWrite) {
      onChange(valueToWrite);
    }
  }, [formData, valueToWrite, onChange]);

  if (hidden) {
    return null;
  }

  const formattedSum = sanitizeNumericInput(sum.toString(), 'decimal-number');

  // The summary variant renders the total as text, with @sum in the title
  // replaced by the sum. It's colored by the last validation of the sum match
  // rule comparing its source fields.
  if (uiSchema?.['misc:variant'] === 'summary') {
    const total = (schema?.title ?? '').replace('@sum', formattedSum);
    const rule = findSumMatchRule(sumMatchRules, sourceFields);
    const status = rule && !shouldRenderPreview ? sumMatchStates[rule.path]?.status : undefined;

    return (
      <div
        className={`hdbt-form--computed-sum-summary${status ? ` hdbt-form--computed-sum-summary--${status}` : ''}`}
        id={idSchema.$id}
      >
        <p className='hdbt-form--computed-sum-summary__total'>{total}</p>
        {schema?.description && !shouldRenderPreview && (
          <p className='hdbt-form--computed-sum-summary__description'>{schema.description}</p>
        )}
      </div>
    );
  }

  if (shouldRenderPreview) {
    return <PreviewInput label={schema?.title} value={formattedSum} uiSchema={uiSchema} />;
  }

  return (
    <TextInput
      {...({
        'aria-invalid': Boolean(rawErrors?.length) || undefined,
        className: 'hdbt-form--field--computed-sum',
        errorText: formatErrors(rawErrors),
        id: idSchema.$id,
        invalid: Boolean(rawErrors?.length),
        label: schema?.title,
        name,
        readOnly: true,
        value: formattedSum,
      } as ComponentPropsWithRef<typeof TextInput>)}
    />
  );
};

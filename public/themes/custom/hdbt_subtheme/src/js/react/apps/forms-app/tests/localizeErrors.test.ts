import type { RJSFSchema } from '@rjsf/utils';
import { customizeValidator } from '@rjsf/validator-ajv8';
import { describe, expect, it } from 'vitest';
import { localizeErrors } from '../localizeErrors';

const validator = customizeValidator({ ajvOptionsOverrides: { allErrors: true, coerceTypes: false } }, localizeErrors);

const schema: RJSFSchema = {
  type: 'object',
  properties: {
    acting_year: { title: 'Acting year', type: 'string', enum: ['2027'] },
    untitled: { type: 'string', enum: ['1', '3'] },
  },
};

describe('localizeErrors', () => {
  it('shows a value outside the allowed options as a missing value', () => {
    const { errors } = validator.validateFormData({ acting_year: '2026' }, schema);

    expect(errors.map((error) => error.message)).toEqual(['Acting year field is required.']);
  });

  it('falls back to a generic message when the field has no title', () => {
    const { errors } = validator.validateFormData({ untitled: 'Valtio' }, schema);

    expect(errors.map((error) => error.message)).toEqual(['Field is required']);
  });

  it('accepts an allowed value', () => {
    const { errors } = validator.validateFormData({ acting_year: '2027', untitled: '3' }, schema);

    expect(errors).toEqual([]);
  });
});

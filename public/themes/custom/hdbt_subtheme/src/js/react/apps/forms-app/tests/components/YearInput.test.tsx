import { fireEvent, render } from '@testing-library/react';
import type { WidgetProps } from '@rjsf/utils';
import { describe, expect, it, vi } from 'vitest';
import { TextInput } from '../../components/Input';
import { formStateAtom, initializeFormAtom } from '../../store';
import { testResponseData } from '../../testutils/Data';
import { TestProvider } from '../../testutils/TestProvider';

const yearSchema = {
  title: 'Year',
  type: 'string',
  format: 'year',
  maxLength: 4,
  pattern: '^(19[0-9]{2}|20[0-9]{2}|2100)$',
};

const renderInput = (schema: object, onChange = vi.fn()) => {
  const { container } = render(
    <TestProvider
      initialValues={[
        [initializeFormAtom, testResponseData],
        // The test schema has no steps of its own, which lands the form on the
        // preview step, where inputs render as plain text.
        [formStateAtom, { currentStep: [0, { id: 'step-1', label: 'Step 1' }], reachedStep: 0 }],
      ]}
    >
      <TextInput
        {...({
          id: 'year',
          label: 'Year',
          name: 'year',
          onChange,
          schema,
          value: '',
        } as unknown as WidgetProps)}
      />
    </TestProvider>,
  );

  const input = container.querySelector('input');
  if (!input) {
    throw new Error('The input did not render');
  }

  return { input, onChange };
};

describe('The year input', () => {
  it('Only accepts digits', () => {
    const { input, onChange } = renderInput(yearSchema);

    fireEvent.change(input, { target: { value: '2o2４v' } });

    expect(onChange).toHaveBeenCalledWith('22');
  });

  it('Caps the value at four characters', () => {
    const { input } = renderInput(yearSchema);

    expect(input.getAttribute('maxlength')).toEqual('4');
  });

  it('Asks for a numeric keyboard', () => {
    const { input } = renderInput(yearSchema);

    expect(input.getAttribute('inputmode')).toEqual('numeric');
  });

  it('Leaves a plain string field alone', () => {
    const { input, onChange } = renderInput({ title: 'Name', type: 'string' });

    fireEvent.change(input, { target: { value: 'Kaupunki 2024' } });

    expect(onChange).toHaveBeenCalledWith('Kaupunki 2024');
    expect(input.getAttribute('inputmode')).toBeNull();
  });

  it('Does not cap a string field that declares no limit', () => {
    const { input } = renderInput({ title: 'Name', type: 'string' });

    expect(input.getAttribute('maxlength')).toBeNull();
  });
});

import type { WidgetProps } from '@rjsf/utils';
import { fireEvent, render } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { TextInput } from '../../components/Input';
import { formStateAtom, initializeFormAtom } from '../../store';
import { testResponseData } from '../../testutils/Data';
import { TestProvider } from '../../testutils/TestProvider';

const postalCodeSchema = {
  title: 'Postal code',
  type: 'string',
  format: 'postal-code',
  maxLength: 5,
};

const renderInput = (schema: object, value = '', onChange = vi.fn()) => {
  const { container } = render(
    <TestProvider
      initialValues={[
        [initializeFormAtom, testResponseData],
        [formStateAtom, { currentStep: [0, { id: 'step-1', label: 'Step 1' }], reachedStep: 0 }],
      ]}
    >
      <TextInput
        {...({
          id: 'postal_code',
          label: 'Postal code',
          name: 'postal_code',
          onChange,
          schema,
          value,
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

describe('The postal code input', () => {
  it('Only accepts digits', () => {
    const { input, onChange } = renderInput(postalCodeSchema);

    fireEvent.change(input, { target: { value: '00 1a0' } });

    expect(onChange).toHaveBeenCalledWith('0010');
  });

  it('Drops separators such as commas and full stops', () => {
    const { input, onChange } = renderInput(postalCodeSchema);

    fireEvent.change(input, { target: { value: '00,10.0' } });

    expect(onChange).toHaveBeenCalledWith('00100');
  });

  it('Keeps a leading zero', () => {
    const { input, onChange } = renderInput(postalCodeSchema);

    fireEvent.change(input, { target: { value: '00100' } });

    expect(onChange).toHaveBeenCalledWith('00100');
  });

  it('Caps the value at five characters', () => {
    const { input } = renderInput(postalCodeSchema);

    expect(input.getAttribute('maxlength')).toEqual('5');
  });

  it('Asks for a numeric keyboard', () => {
    const { input } = renderInput(postalCodeSchema);

    expect(input.getAttribute('inputmode')).toEqual('numeric');
  });

  it('Does not show a character counter', () => {
    const { input } = renderInput(postalCodeSchema);

    expect(input.parentElement?.parentElement?.textContent?.includes('/5')).toEqual(false);
  });
});

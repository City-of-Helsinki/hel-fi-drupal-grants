import type { WidgetProps } from '@rjsf/utils';
import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, test, vi } from 'vitest';
import { SelectWidget } from '../components/Input';
import { formConfigAtom, formStateAtom } from '../store';
import { TestProvider } from '../testutils/TestProvider';

const enumOptions = [
  { label: 'Football', value: 'football' },
  { label: 'Swimming', value: 'swimming' },
  { label: 'Diving', value: 'diving' },
];

const renderSelect = ({
  multiple = true,
  onChange = vi.fn(),
  preview = false,
  value,
}: {
  multiple?: boolean;
  onChange?: WidgetProps['onChange'];
  preview?: boolean;
  value?: unknown;
}) =>
  render(
    <TestProvider
      initialValues={[
        [formConfigAtom, { submitState: 'DRAFT', grantsProfile: {} }],
        [
          formStateAtom,
          {
            currentStep: [0, { id: preview ? 'preview' : 'step', label: 'Step' }],
            validationCutoff: 0,
          },
        ],
      ]}
    >
      <SelectWidget
        {...({
          id: 'sports',
          label: 'Sports',
          multiple,
          onChange,
          options: { enumOptions },
          value,
        } as unknown as WidgetProps)}
      />
    </TestProvider>,
  );

describe('SelectWidget', () => {
  test('returns the selected values as an array in multiselect mode', () => {
    const onChange = vi.fn();
    renderSelect({ onChange, value: ['diving'] });

    fireEvent.click(screen.getByRole('combobox', { name: /Sports/ }));
    fireEvent.click(screen.getByRole('option', { name: /Football/ }));

    expect(onChange).toHaveBeenLastCalledWith(['football', 'diving']);
  });

  test('returns undefined when every option is deselected', () => {
    const onChange = vi.fn();
    renderSelect({ onChange, value: ['diving'] });

    fireEvent.click(screen.getByRole('combobox', { name: /Sports/ }));
    fireEvent.click(screen.getByRole('option', { name: /Diving/ }));

    expect(onChange).toHaveBeenLastCalledWith(undefined);
  });

  test('shows the labels of the selected options in the preview', () => {
    const { container } = renderSelect({ preview: true, value: ['football', 'diving'] });

    expect(container.textContent).toContain('Football, Diving');
  });

  test('shows a dash in the preview when nothing is selected', () => {
    const { container } = renderSelect({ preview: true, value: [] });

    expect(container.textContent).toBe('Sports-');
  });

  test('shows the label of a single selected option in the preview', () => {
    const { container } = renderSelect({ multiple: false, preview: true, value: 'swimming' });

    expect(container.textContent).toContain('Swimming');
  });
});

import type { WidgetProps } from '@rjsf/utils';
import { fireEvent, render } from '@testing-library/react';
import { useState } from 'react';
import { describe, expect, test, vi } from 'vitest';
import { DateWidget } from '../components/Input';
import { formConfigAtom, formStateAtom } from '../store';
import { TestProvider } from '../testutils/TestProvider';

const Wrapper = ({ initialValue, onChange }: { initialValue?: string; onChange: (value: unknown) => void }) => {
  const [value, setValue] = useState<unknown>(initialValue);

  return (
    <DateWidget
      {...({
        id: 'date',
        label: 'Date',
        onChange: (next: unknown) => {
          onChange(next);
          setValue(next);
        },
        value,
      } as unknown as WidgetProps)}
    />
  );
};

const renderDate = (initialValue?: string) => {
  const onChange = vi.fn();
  render(
    <TestProvider
      initialValues={[
        [formConfigAtom, { submitState: 'DRAFT', grantsProfile: {} }],
        [formStateAtom, { currentStep: [0, { id: 'step', label: 'Step' }], reachedStep: 0 }],
      ]}
    >
      <Wrapper initialValue={initialValue} onChange={onChange} />
    </TestProvider>,
  );
  const input = document.getElementById('date') as HTMLInputElement;
  const enter = (text: string) => {
    fireEvent.change(input, { target: { value: text } });
    fireEvent.blur(input);
  };

  return { enter, input, onChange };
};

describe('DateWidget', () => {
  test('stores a typed date as YYYY-MM-DD', () => {
    const { enter, input, onChange } = renderDate();
    enter('5.3.2026');
    expect(onChange).toHaveBeenLastCalledWith('2026-03-05');
    expect(input.value).toBe('5.3.2026');
  });

  test('leaving an empty date field stores nothing', () => {
    const { enter, input, onChange } = renderDate();
    enter('');
    expect(onChange).toHaveBeenLastCalledWith(undefined);
    expect(input.value).toBe('');
  });

  test('partial input is kept on screen but not stored', () => {
    const { enter, input, onChange } = renderDate('2026-03-05');
    enter('5.3.');
    expect(onChange).toHaveBeenLastCalledWith(undefined);
    expect(input.value).toBe('5.3.');
  });

  test('clears an unparseable stored value', () => {
    const { input, onChange } = renderDate('NaN-NaN-NaN');
    expect(onChange).toHaveBeenCalledWith(undefined);
    expect(input.value).toBe('');
  });
});

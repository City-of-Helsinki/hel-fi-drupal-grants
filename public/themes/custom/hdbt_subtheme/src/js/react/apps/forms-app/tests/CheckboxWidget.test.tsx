import type { WidgetProps } from '@rjsf/utils';
import { render, screen } from '@testing-library/react';
import { describe, expect, test } from 'vitest';
import { CheckboxWidget } from '../components/Input';
import { formConfigAtom, formStateAtom } from '../store';
import { TestProvider } from '../testutils/TestProvider';

const renderCheckbox = (uiSchema?: WidgetProps['uiSchema']) =>
  render(
    <TestProvider
      initialValues={[
        [formConfigAtom, { submitState: 'DRAFT', grantsProfile: {} }],
        [formStateAtom, { currentStep: [0, { id: 'step', label: 'Step' }], validationCutoff: 0 }],
      ]}
    >
      <CheckboxWidget
        {...({
          id: 'confirm',
          label: 'We confirm the rents',
          onChange: () => {},
          required: true,
          uiSchema,
          value: false,
        } as unknown as WidgetProps)}
      />
    </TestProvider>,
  );

describe('CheckboxWidget', () => {
  test('uses the field title as the checkbox label by default', () => {
    renderCheckbox();

    expect(screen.getByLabelText('We confirm the rents *')).toBeTruthy();
    expect(document.querySelector('fieldset')).toBeNull();
  });

  test('renders the title as a fieldset heading when checkboxLabel is set', () => {
    renderCheckbox({ 'ui:options': { checkboxLabel: 'Yes' } });

    expect(screen.getByLabelText('Yes')).toBeTruthy();
    expect(document.querySelector('fieldset')?.textContent).toContain('We confirm the rents *');
  });
});

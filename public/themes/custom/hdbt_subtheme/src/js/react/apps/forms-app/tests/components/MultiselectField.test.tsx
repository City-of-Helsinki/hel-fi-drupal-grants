import Form from '@rjsf/core';
import type { RJSFSchema, UiSchema } from '@rjsf/utils';
import validator from '@rjsf/validator-ajv8';
import { render, screen } from '@testing-library/react';
import { describe, expect, test } from 'vitest';
import { SelectWidget } from '../../components/Input';
import { FieldTemplate } from '../../components/Templates';
import { formConfigAtom, formStateAtom } from '../../store';
import { TestProvider } from '../../testutils/TestProvider';

const schema: RJSFSchema = {
  type: 'object',
  properties: {
    sports: {
      title: 'Which sports does the activity include?',
      type: 'array',
      uniqueItems: true,
      items: { type: 'string', enum: ['football', 'swimming', 'diving'] },
    },
  },
};

const uiSchema: UiSchema = {
  'ui:globalOptions': { label: false },
  sports: { 'ui:enumNames': ['Football', 'Swimming', 'Diving'] },
};

const renderForm = () =>
  render(
    <TestProvider
      initialValues={[
        [formConfigAtom, { submitState: 'DRAFT', grantsProfile: {} }],
        [formStateAtom, { currentStep: [0, { id: 'step', label: 'Step' }], validationCutoff: 0 }],
      ]}
    >
      <Form
        formData={{ sports: ['football', 'diving'] }}
        schema={schema}
        templates={{ FieldTemplate }}
        uiSchema={uiSchema}
        validator={validator}
        widgets={{ SelectWidget }}
      >
        <p></p>
      </Form>
    </TestProvider>,
  );

describe('Multiselect field', () => {
  test('renders the title only once', () => {
    renderForm();

    expect(screen.getAllByText('Which sports does the activity include?')).toHaveLength(1);
  });

  test('does not render the selected options as tags', () => {
    renderForm();

    // The selection is still shown in the select itself.
    const selection = screen.getByRole('combobox').textContent;
    expect(selection).toContain('Football');
    expect(selection).toContain('Diving');
    expect(document.getElementById('root_sports-tag-list')).toBeNull();
  });
});

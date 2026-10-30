import Form, { getDefaultRegistry } from '@rjsf/core';
import type { CustomValidator, RJSFSchema, UiSchema } from '@rjsf/utils';
import validator from '@rjsf/validator-ajv8';
import { act, render } from '@testing-library/react';
import { atom } from 'jotai';
import { createRef } from 'react';
import { describe, expect, test, vi } from 'vitest';

vi.mock('hds-react', async (orig) => {
  const actual = (await orig()) as Record<string, unknown>;
  return {
    ...actual,
    // jsdom can't render HDS FileInput; keep its id contract: <input type="file" id={id}>.
    FileInput: ({ id }: { id: string }) => <input type='file' id={id} />,
  };
});

import { ComputedSum } from '../components/Fields/ComputedSum';
import { SubventionTable } from '../components/Fields/SubventionTable';
import { FileInput } from '../components/FileInput';
import { CheckboxWidget, DateWidget, RadioWidget, SelectWidget, TextArea, TextInput } from '../components/Input';
import { ArrayFieldTemplate, FieldTemplate, ObjectFieldTemplate } from '../components/Templates';
import { formConfigAtom, formDataAtomRef, formStateAtom, formStepsAtom } from '../store';
import { TestProvider } from '../testutils/TestProvider';
import { focusError } from '../utils';

const widgets = {
  DateWidget,
  RadioWidget,
  SelectWidget,
  TextareaWidget: TextArea,
  TextWidget: TextInput,
  CheckboxWidget,
};
globalThis.DataTransfer ??= class {
  items = { add() {} };
  files = [];
} as never;
const fields = {
  ...getDefaultRegistry().fields,
  atvFile: FileInput,
  computedSum: ComputedSum,
  subventionTable: SubventionTable,
};

type Case = {
  name: string;
  expected: string;
  property: RJSFSchema;
  ui?: UiSchema;
  data?: unknown;
  custom?: boolean;
};

const fileProps = {
  type: 'object',
  properties: {
    fileName: { type: 'string' },
    integrationID: { type: 'string' },
    isDeliveredLater: { type: 'boolean' },
    isIncludedInOtherFile: { type: 'boolean' },
  },
} as RJSFSchema;

const cases: Case[] = [
  { name: 'text', expected: 'root_step_field', property: { type: 'string', title: 'Text', minLength: 1 } },
  { name: 'integer', expected: 'root_step_field', property: { type: 'integer', title: 'Int' } },
  {
    name: 'textarea',
    expected: 'root_step_field',
    property: { type: 'string', title: 'Area', minLength: 1 },
    ui: { 'ui:widget': 'textarea' },
  },
  {
    name: 'select',
    expected: 'root_step_field-main-button',
    property: { type: 'string', title: 'Select', enum: ['a', 'b'] },
  },
  {
    name: 'radio',
    expected: 'root_step_field_a',
    property: { type: 'string', title: 'Radio', enum: ['a', 'b'] },
    ui: { 'ui:widget': 'radio' },
  },
  {
    name: 'date',
    expected: 'root_step_field',
    property: { type: 'string', title: 'Date', format: 'date' },
    ui: { 'ui:widget': 'date' },
  },
  { name: 'checkbox', expected: 'root_step_field', property: { type: 'boolean', title: 'Check' } },
  {
    name: 'the read-only computedSum total, not its editable siblings',
    expected: 'root_step_field',
    property: { type: 'string', title: 'Total' },
    ui: { 'ui:field': 'computedSum', 'ui:options': { sourceFields: [] } },
    custom: true,
  },
  {
    name: 'atvFile',
    expected: 'field',
    property: fileProps,
    ui: { 'ui:field': 'atvFile' },
    data: {},
    custom: true,
  },
  {
    name: 'subventionTable',
    expected: 'root_step_field-1',
    property: {
      type: 'array',
      title: 'Subventions',
      items: { type: 'array', items: [{ type: 'object' }, { type: 'object' }] },
      options: [
        { id: '1', label: 'A' },
        { id: '2', label: 'B' },
      ],
    } as RJSFSchema,
    ui: { 'ui:field': 'subventionTable' },
    custom: true,
  },
];

const renderAndValidate = async ({ name, property, ui, data, custom }: Case) => {
  const schema: RJSFSchema = {
    type: 'object',
    properties: {
      step: {
        type: 'object',
        properties: name.includes('computedSum')
          ? { source: { type: 'integer', title: 'Source' }, field: property }
          : { field: property },
        required: ['field'],
      },
    },
    required: ['step'],
  };
  const uiSchema: UiSchema = { step: { field: ui ?? {} } };
  const formData = { step: data === undefined ? {} : { field: data } };
  const customValidate: CustomValidator | undefined = custom
    ? (_data, errors) => {
        // biome-ignore lint/suspicious/noExplicitAny: RJSF error handler is untyped
        const target = (errors as any).step?.field;
        target?.addError('custom error');
        return errors;
      }
    : undefined;

  const ref = createRef<Form>();
  const step = { id: 'step', label: 'Step' };
  const steps = new Map([[0, step]]);
  const dataAtom = atom(formData);
  render(
    <TestProvider
      initialValues={[
        [formConfigAtom, { submitState: 'DRAFT', token: 't', grantsProfile: {}, applicationNumber: 'X' }],
        [formStateAtom, { currentStep: [0, step], reachedStep: 0 }],
        [formStepsAtom, steps],
        [formDataAtomRef, dataAtom],
      ]}
    >
      <Form
        customValidate={customValidate}
        fields={fields}
        formData={formData}
        noHtml5Validate
        onError={(errors) => errors.some((error) => focusError(ref.current?.formElement.current, error))}
        ref={ref}
        schema={schema}
        showErrorList={false}
        templates={{ ArrayFieldTemplate, FieldTemplate, ObjectFieldTemplate }}
        uiSchema={uiSchema}
        validator={validator}
        widgets={widgets}
      >
        <button type='button'>noop</button>
      </Form>
    </TestProvider>,
  );

  await act(async () => {
    ref.current?.validateForm();
  });

  return document.activeElement?.id;
};

describe('focusError', () => {
  for (const c of cases) {
    test(`focuses ${c.name}`, async () => {
      expect(await renderAndValidate(c)).toBe(c.expected);
    });
  }

  test('scrolls the whole field into view and marks the total invalid', async () => {
    const scrolled: Element[] = [];
    const spy = vi.spyOn(Element.prototype, 'scrollIntoView').mockImplementation(function (this: Element) {
      scrolled.push(this);
    });
    const computedSum = cases.find(({ name }) => name.includes('computedSum')) as Case;

    await renderAndValidate(computedSum);
    const total = document.activeElement as HTMLInputElement;

    expect(total.readOnly).toBe(true);
    expect(total.disabled).toBe(false);
    expect(total.getAttribute('aria-invalid')).toBe('true');
    expect(scrolled).toEqual([total.closest('.form-group')]);
    spy.mockRestore();
  });
});

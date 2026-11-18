import type { RJSFSchema, UiSchema } from '@rjsf/utils';
import { act, fireEvent, render, screen, within } from '@testing-library/react';
import { createStore, Provider } from 'jotai';
import { describe, expect, test, vi } from 'vitest';
import { RJSFFormContainer } from '../../containers/RJSFFormContainer';
import { createFormDataAtom, formDataAtomRef, formStateAtom, initializeFormAtom } from '../../store';
import { testGrantsProfile } from '../../testutils/Data';

const schema: RJSFSchema = {
  type: 'object',
  properties: {
    first: {
      type: 'object',
      title: 'First',
      properties: {
        section: { type: 'object', title: 'Section', properties: { note: { type: 'string', title: 'Note' } } },
      },
    },
    grants: {
      type: 'object',
      title: 'Grants',
      properties: {
        section: {
          type: 'object',
          title: 'Subventions',
          required: ['name'],
          properties: {
            name: { type: 'string', title: 'Name' },
            subventions: { title: 'Subventions', options: [{ id: 1, label: 'Subvention' }] } as RJSFSchema,
          },
        },
      },
    },
  },
};

const uiSchema: UiSchema = { grants: { section: { subventions: { 'ui:field': 'subventionTable' } } } };

const renderForm = (applicationNumber?: string) => {
  const store = createStore();
  store.set(initializeFormAtom, {
    applicationNumber,
    grants_profile: testGrantsProfile,
    schema,
    settings: {},
    status: 'DRAFT',
    translations: {},
    ui_schema: uiSchema,
  } as never);
  const formDataAtom = createFormDataAtom(`step-errors-${Math.random()}`, {
    grants: { section: { subventions: [['1', { value: '' }]] } },
  });
  store.set(formDataAtomRef, formDataAtom);

  render(
    <Provider store={store}>
      <RJSFFormContainer
        formDataAtom={formDataAtom}
        saveDraft={vi.fn()}
        schema={schema}
        submitData={vi.fn()}
        uiSchema={uiSchema}
      />
    </Provider>,
  );

  return store;
};

const fieldErrors = () =>
  Array.from(document.querySelectorAll('[id$="-error"]'))
    .filter((element) => element.textContent)
    .map((element) => element.id);
const clickStep = (label: string) =>
  fireEvent.click(
    within(document.querySelector('.hdbt-form--stepper') as HTMLElement).getByRole('button', {
      name: new RegExp(label),
    }),
  );
const subventionErrors = () => screen.queryAllByText('subvention.greater_than_zero');

describe('Errors on pages the user has not reached', () => {
  test('are not shown after moving to the page with the next button', async () => {
    const store = renderForm();

    await act(async () => {
      fireEvent.click(screen.getByText('Next'));
    });

    expect(store.get(formStateAtom)?.currentStep[0]).toBe(1);
    expect(fieldErrors()).toEqual([]);
    expect(subventionErrors()).toHaveLength(0);
  });

  test('are not shown after moving to the page with the stepper', async () => {
    const store = renderForm();

    await act(async () => {
      clickStep('Grants');
    });

    expect(store.get(formStateAtom)?.currentStep[0]).toBe(1);
    expect(fieldErrors()).toEqual([]);
    expect(subventionErrors()).toHaveLength(0);
  });

  test('are shown when moving on from the page', async () => {
    const store = renderForm();

    await act(async () => {
      clickStep('Grants');
    });
    await act(async () => {
      fireEvent.click(screen.getByText('Preview'));
    });

    expect(store.get(formStateAtom)?.currentStep[0]).toBe(1);
    expect(fieldErrors()).toEqual(['root_grants_section_name-error']);
    expect(subventionErrors().length).toBeGreaterThan(0);
  });

  test('are shown again after reloading the form', async () => {
    sessionStorage.setItem('TEST-RELOAD-form-state', JSON.stringify({ currentStep: 1, validationCutoff: 1 }));

    await act(async () => {
      renderForm('TEST-RELOAD');
    });

    expect(fieldErrors()).toEqual(['root_grants_section_name-error']);
    expect(subventionErrors().length).toBeGreaterThan(0);
    // The errors are shown without moving focus to them.
    expect(document.activeElement?.id).not.toBe('root_grants_section_name');
  });

  test('stay hidden after reloading the form if the page was not validated', async () => {
    sessionStorage.setItem('TEST-RELOAD-2-form-state', JSON.stringify({ currentStep: 1, validationCutoff: 0 }));

    await act(async () => {
      renderForm('TEST-RELOAD-2');
    });

    expect(fieldErrors()).toEqual([]);
    expect(subventionErrors()).toHaveLength(0);
  });
});

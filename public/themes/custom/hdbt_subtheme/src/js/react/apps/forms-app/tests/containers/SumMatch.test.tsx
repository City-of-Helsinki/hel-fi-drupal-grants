import type { RJSFSchema, UiSchema } from '@rjsf/utils';
import { act, fireEvent, render, screen } from '@testing-library/react';
import { createStore, Provider } from 'jotai';
import { describe, expect, test, vi } from 'vitest';
import { RJSFFormContainer } from '../../containers/RJSFFormContainer';
import { createFormDataAtom, formDataAtomRef, formStateAtom, initializeFormAtom } from '../../store';
import { testGrantsProfile } from '../../testutils/Data';

const integer = (title: string): RJSFSchema => ({ title, type: 'integer' });

const schema: RJSFSchema = {
  type: 'object',
  properties: {
    step: {
      type: 'object',
      title: 'Step',
      properties: {
        section: {
          type: 'object',
          title: 'Section',
          properties: {
            ages: {
              type: 'object',
              title: 'Ages',
              properties: {
                minors: integer('Minors'),
                adults: integer('Adults'),
                total: { title: 'Total @sum', type: 'string' },
              },
            },
            genders: {
              type: 'object',
              title: 'Genders',
              properties: {
                women: integer('Women'),
                men: integer('Men'),
                total: { title: 'Total @sum', type: 'string' },
              },
            },
            status: { type: 'null' },
          },
        },
      },
    },
  },
};

const ages = ['step.section.ages.minors', 'step.section.ages.adults'];
const genders = ['step.section.genders.women', 'step.section.genders.men'];
const summary = (sourceFields: string[]) => ({
  'misc:exclude-from-submit': true,
  'misc:variant': 'summary',
  'ui:field': 'computedSum',
  'ui:options': { sourceFields },
});

const uiSchema: UiSchema = {
  step: {
    section: {
      ages: { total: summary(ages) },
      genders: { total: summary(genders) },
      status: {
        'misc:exclude-from-submit': true,
        'misc:sum-match': { compareTo: ages, fields: genders, message: 'mismatch', successMessage: 'match' },
        'ui:field': 'sumMatchStatus',
      },
    },
  },
};

const renderForm = () => {
  const store = createStore();
  store.set(initializeFormAtom, {
    grants_profile: testGrantsProfile,
    schema,
    settings: {},
    status: 'DRAFT',
    translations: {},
    ui_schema: uiSchema,
  } as never);
  const formDataAtom = createFormDataAtom(`sum-match-${Math.random()}`, {});
  store.set(formDataAtomRef, formDataAtom);

  const result = render(
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

  return { ...result, store };
};

const input = (path: string) => document.getElementById(`root_${path.replaceAll('.', '_')}`) as HTMLInputElement;
const type = (path: string, value: string) => fireEvent.change(input(path), { target: { value } });
const leave = (path: string, to: Element | null) => fireEvent.focusOut(input(path), { relatedTarget: to });
const status = () => document.getElementById('root_step_section_status') as HTMLElement;
const totals = () => Array.from(document.querySelectorAll('.hdbt-form--computed-sum-summary'));

describe('Sum match validation', () => {
  test('updates totals as you type and validates only after leaving a group with both groups filled', () => {
    renderForm();

    type('step.section.ages.minors', '200');
    type('step.section.ages.adults', '160');
    expect(totals()[0].textContent).toBe('Total 360');

    // Moving from the ages to the empty genders group doesn't validate yet.
    leave('step.section.ages.adults', input('step.section.genders.women'));
    expect(status().textContent).toBe('');

    type('step.section.genders.women', '170');
    // Moving within the genders group doesn't validate either.
    leave('step.section.genders.women', input('step.section.genders.men'));
    expect(status().textContent).toBe('');

    type('step.section.genders.men', '180');
    leave('step.section.genders.men', null);
    expect(status().textContent).toBe('mismatch');
    expect(totals().map((total) => total.className)).toEqual([
      'hdbt-form--computed-sum-summary hdbt-form--computed-sum-summary--mismatch',
      'hdbt-form--computed-sum-summary hdbt-form--computed-sum-summary--mismatch',
    ]);
    // No single field is marked invalid, as it's unknown which number is wrong.
    expect(document.querySelectorAll('[aria-invalid="true"]')).toHaveLength(0);

    type('step.section.genders.women', '180');
    leave('step.section.genders.women', null);
    expect(status().textContent).toBe('match');
    expect(totals()[1].className).toContain('--match');
  });

  test('blocks moving to the next step and focuses the status on mismatch', async () => {
    const { store } = renderForm();

    type('step.section.ages.minors', '360');
    type('step.section.genders.women', '350');

    await act(async () => {
      fireEvent.click(screen.getByText('Preview'));
    });

    expect(store.get(formStateAtom)?.currentStep[0]).toBe(0);
    expect(document.activeElement).toBe(status());
    expect(status().getAttribute('role')).toBe('status');
    expect(status().textContent).toBe('mismatch');
  });
});

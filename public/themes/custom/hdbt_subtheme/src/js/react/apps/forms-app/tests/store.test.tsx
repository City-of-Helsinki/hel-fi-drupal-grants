import type { RJSFSchema } from '@rjsf/utils';
import { render } from '@testing-library/react';
import { createStore, useAtomValue } from 'jotai';
import { beforeEach, describe, expect, test } from 'vitest';
import {
  createFormDataAtom,
  discardStaleSession,
  formStateAtom,
  getFormConfigAtom,
  initializeFormAtom,
  raiseValidationCutoffAtom,
  setStepAtom,
} from '../store';
import { testGrantsProfile } from '../testutils/Data';

const Uninitialized = () => {
  useAtomValue(getFormConfigAtom);

  return null;
};

const schema: RJSFSchema = {
  type: 'object',
  properties: {
    first: { type: 'object', title: 'First' },
    second: { type: 'object', title: 'Second' },
  },
};

const initialize = (applicationNumber = 'TEST-1') => {
  const store = createStore();
  store.set(initializeFormAtom, {
    applicationNumber,
    grants_profile: testGrantsProfile,
    schema,
    settings: {},
    status: 'DRAFT',
    translations: {},
    ui_schema: {},
  } as never);

  return store;
};

const currentStep = (store: ReturnType<typeof createStore>) => store.get(formStateAtom)?.currentStep[0];
const validationCutoff = (store: ReturnType<typeof createStore>) => store.get(formStateAtom)?.validationCutoff;

describe('Store tests', () => {
  beforeEach(() => {
    sessionStorage.clear();
  });

  test('Accessing form state before initialization throws error', () => {
    expect(() => render(<Uninitialized />)).toThrow('Trying to read form config before initialization.');
  });

  test('Starts from the first step without validating anything', () => {
    const store = initialize();

    expect(currentStep(store)).toBe(0);
    expect(validationCutoff(store)).toBe(-1);
  });

  test('Continues from the persisted step and validation cutoff after reload', () => {
    const store = initialize();
    store.set(raiseValidationCutoffAtom, 0);
    store.set(setStepAtom, 1);

    const reloaded = initialize();

    expect(currentStep(reloaded)).toBe(1);
    expect(validationCutoff(reloaded)).toBe(0);
  });

  test('Keeps the persisted state of each application separate', () => {
    const store = initialize('TEST-1');
    store.set(setStepAtom, 1);

    expect(currentStep(initialize('TEST-2'))).toBe(0);
  });

  test('Ignores a persisted step that does not fit the form', () => {
    sessionStorage.setItem('TEST-1-form-state', JSON.stringify({ currentStep: 99, validationCutoff: 0 }));
    expect(currentStep(initialize())).toBe(0);

    // The ready step is never accessible.
    sessionStorage.setItem('TEST-1-form-state', JSON.stringify({ currentStep: 3, validationCutoff: 2 }));
    expect(currentStep(initialize())).toBe(0);

    sessionStorage.setItem('TEST-1-form-state', '{');
    expect(currentStep(initialize())).toBe(0);
  });

  describe('When the server copy is newer than the session copy', () => {
    const persistSession = (timestamp: number) => {
      sessionStorage.setItem('TEST-1', JSON.stringify({ timestamp, data: { cached: true } }));
      sessionStorage.setItem('TEST-1-form-state', JSON.stringify({ currentStep: 1, validationCutoff: 0 }));
    };

    test('Discards the session copy and starts from the first step', () => {
      persistSession(100);
      discardStaleSession('TEST-1', 200);

      expect(createStore().get(createFormDataAtom('TEST-1', { server: true }))).toEqual({ server: true });
      expect(currentStep(initialize())).toBe(0);
      expect(validationCutoff(initialize())).toBe(-1);
    });

    test('Keeps the session copy if it is as new as the server copy', () => {
      persistSession(200);
      discardStaleSession('TEST-1', 200);

      expect(createStore().get(createFormDataAtom('TEST-1', { server: true }))).toEqual({ cached: true });
      expect(currentStep(initialize())).toBe(1);
    });

    test('Keeps the session copy if the server copy has no timestamp', () => {
      persistSession(100);
      discardStaleSession('TEST-1');

      expect(currentStep(initialize())).toBe(1);
    });
  });
});

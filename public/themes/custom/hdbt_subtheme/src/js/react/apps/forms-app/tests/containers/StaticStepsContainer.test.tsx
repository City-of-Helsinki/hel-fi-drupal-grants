import { render } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { StaticStepsContainer } from '../../containers/StaticStepsContainer';
import { createFormDataAtom, formStateAtom } from '../../store';
import { initializeFormState } from '../../testutils/Helpers';
import { TestProvider } from '../../testutils/TestProvider';

describe('StaticStepsContainer.tsx', () => {
  it('Renders the preview title on the preview step', () => {
    const formDataAtom = createFormDataAtom('test', {});
    const { container } = render(
      <TestProvider
        initialValues={[
          [formStateAtom, initializeFormState({ currentStep: [0, { id: 'preview', label: 'Preview' }] })],
        ]}
      >
        <StaticStepsContainer formDataAtom={formDataAtom} schema={{}} />
      </TestProvider>,
    );

    expect(container.querySelector('h2.grants-form__page-title')).toBeTruthy();
  });

  it('Renders nothing on the ready step', () => {
    const formDataAtom = createFormDataAtom('test-ready', {});
    const { container } = render(
      <TestProvider
        initialValues={[[formStateAtom, initializeFormState({ currentStep: [1, { id: 'ready', label: 'Ready' }] })]]}
      >
        <StaticStepsContainer formDataAtom={formDataAtom} schema={{}} />
      </TestProvider>,
    );

    expect(container.firstChild).toBeNull();
  });
});

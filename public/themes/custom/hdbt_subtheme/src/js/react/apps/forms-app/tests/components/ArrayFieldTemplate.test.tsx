import type { ArrayFieldTemplateProps } from '@rjsf/utils';
import { render, screen } from '@testing-library/react';
import { describe, expect, test } from 'vitest';
import { ArrayFieldTemplate } from '../../components/Templates';
import { formConfigAtom, formStateAtom } from '../../store';
import { TestProvider } from '../../testutils/TestProvider';

const renderArray = (rawErrors?: string[]) =>
  render(
    <TestProvider
      initialValues={[
        [formConfigAtom, { submitState: 'DRAFT', grantsProfile: {} }],
        [formStateAtom, { currentStep: [0, { id: 'step', label: 'Step' }], validationCutoff: 0 }],
      ]}
    >
      <ArrayFieldTemplate
        {...({
          canAdd: true,
          idSchema: { $id: 'root_sports' },
          items: [],
          onAddClick: () => {},
          rawErrors,
          registry: { templates: { ArrayFieldItemTemplate: () => null } },
          schema: { type: 'array' },
          uiSchema: {},
        } as unknown as ArrayFieldTemplateProps)}
      />
    </TestProvider>,
  );

describe('ArrayFieldTemplate', () => {
  test('renders array-level errors inline as a focus target', () => {
    renderArray(['At least 2 needed']);

    expect(screen.getByText('At least 2 needed')).toBeTruthy();
    const target = document.getElementById('root_sports');
    expect(target?.hasAttribute('data-focus-on-error')).toBe(true);
  });

  test('renders no error container without errors', () => {
    renderArray();

    expect(document.getElementById('root_sports')).toBeNull();
  });
});

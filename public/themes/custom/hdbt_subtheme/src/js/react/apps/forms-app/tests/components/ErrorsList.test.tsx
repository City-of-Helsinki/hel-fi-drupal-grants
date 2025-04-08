import { describe, expect, it } from 'vitest';
import { render } from '@testing-library/react';

import { TestProvider } from '../../testutils/TestProvider';
import { errorsAtom } from '../../store';
import { ErrorsList } from '../../components/ErrorsList';
import { testKeyedErrors } from '../../testutils/Data';

describe('ErrorList.tsx', async () => {
  render(
    <TestProvider initialValues={[[errorsAtom, testKeyedErrors]]}>
      <ErrorsList />
    </TestProvider>
  );

  const liElements = document.querySelectorAll('li');

  it('Renders error list', () => {
    expect(liElements.length).toBe(2);
    expect(liElements[0].textContent).toBe('Error on page 1. Error 1');
    expect(liElements[1].textContent).toBe('Error on page 2. Error 2');
  });
});

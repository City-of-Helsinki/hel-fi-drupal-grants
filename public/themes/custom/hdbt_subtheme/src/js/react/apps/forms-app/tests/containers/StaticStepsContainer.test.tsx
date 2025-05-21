import { describe } from 'vitest';
import { render } from '@testing-library/react';
import { StaticStepsContainer } from '../../containers/StaticStepsContainer';
import { createFormDataAtom } from '../../store';

describe('StaticStepsContainer.tsx', () => {
  const formDataAtom = createFormDataAtom('test', {});

  render(
    <StaticStepsContainer
      formDataAtom={formDataAtom}
      schema={{}}
    />
  )
});

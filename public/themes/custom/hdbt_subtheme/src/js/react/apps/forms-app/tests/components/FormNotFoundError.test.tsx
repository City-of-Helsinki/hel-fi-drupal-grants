import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { FormNotFoundError } from '../../components/FormNotFoundError';

const formNotFoundErrorText = 'Form not found.';

describe('FormNotFoundError.tsx tests', () => {
  render(<FormNotFoundError />);

  it('Renders general error', () => {
    expect(screen.getByText(formNotFoundErrorText)).toBeTruthy();
  });
});

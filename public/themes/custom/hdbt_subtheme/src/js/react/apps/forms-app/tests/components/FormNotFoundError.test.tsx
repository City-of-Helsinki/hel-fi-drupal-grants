import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { FormNotFoundError } from '../../components/FormNotFoundError';

const formNotFoundErrorText = 'Form not found.';

describe('FormNotFoundError.tsx tests', () => {
  render(<FormNotFoundError />);

  it('Renders general error', () => {
    expect(screen.getByText(formNotFoundErrorText)).toBeTruthy();
  });
});

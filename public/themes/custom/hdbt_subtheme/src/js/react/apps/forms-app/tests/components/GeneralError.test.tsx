import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { GeneralError } from '../../components/GeneralError';

const generalErrorText = 'The application ran into an unrecoverable error. Please refresh the page to continue.';

describe('GeneralError.tsx tests', () => {
  render(<GeneralError />);

  it('Renders general error', () => {
    expect(screen.getByText(generalErrorText)).toBeTruthy();
  });
});

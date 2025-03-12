import { describe, expect, test } from 'vitest';
import { render, screen } from '@testing-library/react';
import { useAtomValue } from 'jotai';
import { getFormConfigAtom } from '../store';

const Uninitialized = () => {
  useAtomValue(getFormConfigAtom);

  return null;
}

describe('Store tests', () => {
  test('Accessing form state before initialization throws error', () => {
    expect(() => render(<Uninitialized />)).toThrow('Trying to read form config before initialization.');
  });
});

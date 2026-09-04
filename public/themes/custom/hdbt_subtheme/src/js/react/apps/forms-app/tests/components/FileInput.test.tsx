import { afterEach, describe, expect, test, vi } from 'vitest';
import { ALLOWED_FORMATS, getAcceptAttribute, getFieldFormats } from '../../components/FileInput';

describe('FileInput.tsx', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('getFieldFormats', () => {
    test('falls back to every allowed format when the field sets none', () => {
      expect(getFieldFormats(undefined)).toEqual(ALLOWED_FORMATS);
      expect(getFieldFormats({})).toEqual(ALLOWED_FORMATS);
      expect(getFieldFormats({ 'misc:formats': [] })).toEqual(ALLOWED_FORMATS);
      expect(getFieldFormats({ 'misc:formats': 'pdf' })).toEqual(ALLOWED_FORMATS);
    });

    test('returns the formats the field allows', () => {
      expect(getFieldFormats({ 'misc:formats': ['pdf'] })).toEqual(['pdf']);
      expect(getFieldFormats({ 'misc:formats': ['pdf', 'doc', 'docx'] })).toEqual(['pdf', 'doc', 'docx']);
    });

    test('omits values outside the allowed list and reports them', () => {
      const spy = vi.spyOn(console, 'error').mockImplementation(() => {});

      expect(getFieldFormats({ 'misc:formats': ['pdf', 'exe', '.pdf', 'PDF'] })).toEqual(['pdf']);
      expect(spy).toHaveBeenCalledTimes(3);
      expect(spy).toHaveBeenCalledWith('Illegal format value passed to FileInput field ', 'exe');
      expect(spy).toHaveBeenCalledWith('Illegal format value passed to FileInput field ', '.pdf');
      expect(spy).toHaveBeenCalledWith('Illegal format value passed to FileInput field ', 'PDF');
    });

    test('falls back to every allowed format when no value survives', () => {
      const spy = vi.spyOn(console, 'error').mockImplementation(() => {});

      expect(getFieldFormats({ 'misc:formats': ['exe', 42] })).toEqual(ALLOWED_FORMATS);
      expect(spy).toHaveBeenCalledTimes(2);
    });
  });

  describe('getAcceptAttribute', () => {
    test('prefixes each format with a dot', () => {
      expect(getAcceptAttribute(['pdf'])).toBe('.pdf');
      expect(getAcceptAttribute(['pdf', 'doc'])).toBe('.pdf,.doc');
    });
  });
});

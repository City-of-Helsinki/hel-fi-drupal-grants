import { vi } from 'vitest';

const interpolate = (template: string, args?: Record<string, string | number>) => {
  if (!args) {
    return template;
  }
  return Object.entries(args).reduce((acc, [key, value]) => acc.replaceAll(key, String(value)), template);
};

const Drupal = { t: (key: string, args?: Record<string, string | number>) => interpolate(key, args) };
vi.stubGlobal('Drupal', Drupal);

const drupalSettings = {
  path: { currentLanguage: 'en' },
  grants_react_form: {
    real_application_number: 'TEST-0000',
    use_preview: false,
    use_empty_preview: false,
    use_draft: true,
    use_print: false,
  },
};
vi.stubGlobal('drupalSettings', drupalSettings);

// HDS produces css parsing errors with jsdom. We don't really care about these.
console.error = (_message, ..._optionalParams) => {};

window.ResizeObserver = class ResizeObserver {
  observe() {}
  unobserve() {}
  disconnect() {}
};

if (!Element.prototype.scrollIntoView) {
  Element.prototype.scrollIntoView = () => {};
}

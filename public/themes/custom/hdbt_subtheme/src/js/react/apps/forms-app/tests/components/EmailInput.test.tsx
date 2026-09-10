import type { WidgetProps } from '@rjsf/utils';
import { render } from '@testing-library/react';
import { customizeValidator } from '@rjsf/validator-ajv8';
import { describe, expect, it, vi } from 'vitest';
import { TextInput } from '../../components/Input';
import { communitySettings } from '../../formConstants';
import { localizeErrors } from '../../localizeErrors';
import { formStateAtom, initializeFormAtom } from '../../store';
import { testResponseData } from '../../testutils/Data';
import { TestProvider } from '../../testutils/TestProvider';

// The applicant email field as the form actually declares it.
const emailSchema = {
  // biome-ignore lint/suspicious/noExplicitAny: reaching into the settings tuple
  ...((communitySettings[1] as any).properties.applicant_email.properties.email as Record<string, unknown>),
  title: 'Email address',
};

const renderInput = (schema: object) => {
  const { container } = render(
    <TestProvider
      initialValues={[
        [initializeFormAtom, testResponseData],
        [formStateAtom, { currentStep: [0, { id: 'step-1', label: 'Step 1' }], reachedStep: 0 }],
      ]}
    >
      <TextInput
        {...({
          id: 'email',
          label: 'Email address',
          name: 'email',
          onChange: vi.fn(),
          schema,
          value: 'matti@hel.fi',
        } as unknown as WidgetProps)}
      />
    </TestProvider>,
  );

  return container;
};

const validator = customizeValidator({ ajvOptionsOverrides: { allErrors: true, coerceTypes: false } }, localizeErrors);

/**
 * Validate a value, collapsing errors the way transformErrors does.
 */
const messagesFor = (value: string): string[] => {
  const { errors } = validator.validateFormData(
    { email: value },
    { type: 'object', properties: { email: emailSchema } },
  );
  const patternFailures = new Set(errors.filter((error) => error.name === 'pattern').map((error) => error.property));

  return errors
    .filter((error) => !(['format', 'maxLength'].includes(error.name ?? '') && patternFailures.has(error.property)))
    .map((error) => error.message ?? '');
};

const domain = (length: number) => {
  const tld = '.fi';
  let remaining = length - tld.length;
  const labels: string[] = [];
  while (remaining > 0) {
    const take = Math.min(63, remaining);
    labels.push('d'.repeat(take));
    remaining -= take;
    if (remaining > 0) {
      remaining -= 1;
    }
  }
  return (labels.join('.') + tld).slice(0, length);
};

const address = (local: number, domainLength: number) => `${'a'.repeat(local)}@${domain(domainLength)}`;

describe('The applicant email field', () => {
  it('Caps the address at 254 characters', () => {
    const input = renderInput(emailSchema).querySelector('input');

    expect(input?.getAttribute('maxlength')).toEqual('254');
  });

  it('Does not show a character counter', () => {
    const container = renderInput(emailSchema);

    expect(container.textContent?.includes('/254')).toEqual(false);
  });

  it('Still shows a counter on a field with a prose limit', () => {
    const container = renderInput({ title: 'Purpose', type: 'string', maxLength: 250 });

    expect(container.textContent?.includes('/250')).toEqual(true);
  });

  it('Accepts an address at the length limit', () => {
    expect(messagesFor(address(64, 189))).toEqual([]);
    expect(messagesFor(address(1, 252))).toEqual([]);
  });

  it('Accepts the length that was reported as breaking submission', () => {
    expect(messagesFor(address(30, 70))).toEqual([]);
  });

  it('Rejects an over-long local part by name', () => {
    expect(messagesFor(address(65, 189))).toEqual([
      'Email address field must have at most 64 characters before the @ sign.',
    ]);
  });

  it('Rejects an over-long address by length', () => {
    expect(messagesFor(address(1, 254))).toEqual(['Email address field must be at most 254 characters.']);
  });

  it('Still reports a malformed address as malformed', () => {
    expect(messagesFor('abc')).toEqual(['The email address abc is not valid. Use the format user@example.com.']);
  });

  it('Accepts an ordinary address', () => {
    expect(messagesFor('matti.meikalainen@hel.fi')).toEqual([]);
  });
});

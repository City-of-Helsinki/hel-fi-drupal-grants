import type { WidgetProps } from '@rjsf/utils';
import { render } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { CommunityOfficialsSelect } from '../../components/Input';
import { formStateAtom, initializeFormAtom } from '../../store';
import { testGrantsProfile, testResponseData } from '../../testutils/Data';
import { TestProvider } from '../../testutils/TestProvider';

// Role 2 is "Contact person" in the role map.
const officials = [
  { official_id: 'official-1', name: 'Tero Testaaja', role: 2, email: 'tero@yhdistys.fi', phone: '0401234567' },
  { official_id: 'official-2', name: 'Maija Meikalainen', role: 7, email: 'maija@yhdistys.fi', phone: '0407654321' },
];

const responseData = {
  ...testResponseData,
  grants_profile: { ...testGrantsProfile, officials },
};

const renderSelect = (value?: string) => {
  const { container } = render(
    <TestProvider
      initialValues={[
        [initializeFormAtom, responseData],
        [formStateAtom, { currentStep: [0, { id: 'step-1', label: 'Step 1' }], reachedStep: 0 }],
      ]}
    >
      <CommunityOfficialsSelect
        {...({
          id: 'official',
          label: 'Select official',
          name: 'official',
          onChange: vi.fn(),
          schema: { type: 'string' },
          value,
        } as unknown as WidgetProps)}
      />
    </TestProvider>,
  );

  const details = container.querySelector('.grants-form--official-details');
  if (!details) {
    throw new Error('The details container did not render');
  }

  return { container, details };
};

describe('The community officials select', () => {
  it('Shows the role, email and phone of the chosen official', () => {
    const { details } = renderSelect('official-1');

    expect(details.textContent).toContain('Contact person');
    expect(details.textContent).toContain('tero@yhdistys.fi');
    expect(details.textContent).toContain('0401234567');
  });

  it('Does not repeat the name, which the select already shows', () => {
    const { details } = renderSelect('official-1');

    expect(details.textContent?.includes('Tero Testaaja')).toEqual(false);
  });

  it('Shows nothing about anyone who is not chosen', () => {
    const { details } = renderSelect('official-1');

    expect(details.textContent?.includes('maija@yhdistys.fi')).toEqual(false);
  });

  it('Shows no details until an official is chosen', () => {
    const { details } = renderSelect();

    expect(details.textContent).toEqual('');
  });

  it('Keeps the details container in place so changes are announced', () => {
    const { details } = renderSelect();

    expect(details.getAttribute('aria-live')).toEqual('polite');
  });
});

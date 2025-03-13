import { describe, expect, it } from 'vitest';
import { render } from '@testing-library/react';
import { TestProvider } from '../../testutils/TestProvider';
import { ApplicantInfo } from '../../components/ApplicantInfo';
import { initializeFormAtom } from '../../store';
import { testResponseData } from '../../testutils/Data';

describe('Applicantinfo.tsx', () => {
  render(
    <TestProvider initialValues={[
      [initializeFormAtom, testResponseData]
    ]}>
      <ApplicantInfo />
    </TestProvider>
  )

  it('Renders expected info', () => {
    const inputs = document.querySelectorAll('input');
    expect(inputs.length).toEqual(7);
  });
});

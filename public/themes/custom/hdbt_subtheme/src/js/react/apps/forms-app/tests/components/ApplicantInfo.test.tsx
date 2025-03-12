import { describe, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { TestProvider } from '../../testutils/TestProvider';
import { ApplicantInfo } from '../../components/ApplicantInfo';
import { initializeFormAtom } from '../../store';
import { testGrantsProfile, testResponseData } from '../../testutils/Data';

describe('Applicantinfo.tsx', () => {
  render(
    <TestProvider initialValues={[
      [initializeFormAtom, testResponseData]
    ]}>
      <ApplicantInfo />
    </TestProvider>
  )

  it('Renders expected info', () => {
    const {
      businessId,
      companyHome,
      companyHomePage,
      companyName,
      companyNameShort,
      foundingYear,
      registrationDate,
    } = testGrantsProfile;

    const inputs = document.querySelectorAll('input');
    expect(inputs.length).toEqual(7);
  });
});

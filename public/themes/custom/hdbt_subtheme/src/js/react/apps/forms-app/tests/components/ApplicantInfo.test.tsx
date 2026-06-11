import { render } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { ApplicantInfo } from '../../components/ApplicantInfo';
import { initializeFormAtom } from '../../store';
import { testResponseData } from '../../testutils/Data';
import { TestProvider } from '../../testutils/TestProvider';

describe('Applicantinfo.tsx', () => {
  render(
    <TestProvider initialValues={[[initializeFormAtom, testResponseData]]}>
      <ApplicantInfo />
    </TestProvider>,
  );

  it('Renders expected info', () => {
    const items = document.querySelectorAll('.prh-content-block__item');
    expect(items.length).toEqual(7);
  });
});

import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, test } from 'vitest';
import DraftsContainer from '../containers/DraftsContainer';
import SentContainer from '../containers/SentContainer';
import { createApplication, createApplications } from './testData';

const visibleNumbers = (container: HTMLElement) =>
  Array.from(container.querySelectorAll('.application-list__item--application-number')).map((el) => el.textContent);

describe('DraftsContainer', () => {
  test('shows an empty message when there are no drafts', () => {
    render(<DraftsContainer applications={[]} />);
    expect(screen.getByText('No applications in progress.')).toBeTruthy();
  });

  test('shows five drafts per page', () => {
    const { container } = render(<DraftsContainer applications={createApplications(7, { status: 'DRAFT' })} />);
    expect(container.querySelectorAll('.application-list__item')).toHaveLength(5);
    expect(container.querySelector('.hds-pagination')).not.toBeNull();
  });
});

describe('SentContainer', () => {
  test('shows an empty message when there are no sent applications', () => {
    const { container } = render(<SentContainer applications={[]} />);
    expect(screen.getByText('No sent applications.')).toBeTruthy();
    expect(container.querySelector('.application-list__item')).toBeNull();
  });

  test('shows the newest applications first with a count of all applications', () => {
    const { container } = render(<SentContainer applications={createApplications(7)} />);
    expect(container.querySelector('.hdbt-search--react__results--title')?.textContent?.trim()).toBe('7 Applications');
    expect(visibleNumbers(container)[0]).toBe('LOCALT-060-0000007');
    expect(container.querySelectorAll('.application-list__item')).toHaveLength(5);
  });

  test('pages with the next button and focuses the first item of the page', () => {
    const { container } = render(<SentContainer applications={createApplications(7)} />);
    fireEvent.click(container.querySelector('.hds-pagination__button-next') as HTMLElement);

    expect(visibleNumbers(container)).toEqual(['LOCALT-060-0000002', 'LOCALT-060-0000001']);
    expect(container.querySelector('.hds-pagination__item-link--active')?.textContent).toBe('2');
    expect(document.activeElement).toBe(container.querySelector('.application-list__item'));
  });

  test('searches when the search form is submitted and returns to the first page', () => {
    const { container } = render(<SentContainer applications={createApplications(7)} />);
    fireEvent.click(container.querySelector('.hds-pagination__button-next') as HTMLElement);

    fireEvent.change(screen.getByLabelText('Search for an application'), { target: { value: '0000003' } });
    expect(container.querySelector('.hdbt-search--react__results--title')?.textContent?.trim()).toBe('7 Applications');

    fireEvent.click(screen.getByRole('button', { name: 'Search' }));
    expect(visibleNumbers(container)).toEqual(['LOCALT-060-0000003']);
    expect(container.querySelector('.hdbt-search--react__results--title')?.textContent?.trim()).toBe('1 Applications');
    expect(container.querySelector('.hds-pagination')).toBeNull();
  });

  test('filters applications being processed when the form is submitted', () => {
    const applications = [
      ...createApplications(2),
      createApplication({ applicationNumber: 'P-1', status: 'PROCESSING' }),
    ];
    const { container } = render(<SentContainer applications={applications} />);
    fireEvent.click(screen.getByLabelText('Show only applications being processed'));
    expect(visibleNumbers(container)).toHaveLength(3);

    fireEvent.click(screen.getByRole('button', { name: 'Search' }));
    expect(visibleNumbers(container)).toEqual(['P-1']);
  });

  test('shows a no results message when nothing matches', () => {
    const { container } = render(<SentContainer applications={createApplications(2)} />);
    fireEvent.change(screen.getByLabelText('Search for an application'), { target: { value: 'nothing' } });
    fireEvent.submit(container.querySelector('form') as HTMLFormElement);
    expect(screen.getByText('No results')).toBeTruthy();
  });

  test('sorts with the sort select', () => {
    const { container } = render(<SentContainer applications={createApplications(3)} />);
    fireEvent.click(screen.getByRole('combobox', { name: /Sort/ }));
    fireEvent.click(screen.getByRole('option', { name: 'Submitted time (oldest first)' }));
    expect(visibleNumbers(container)).toEqual(['LOCALT-060-0000001', 'LOCALT-060-0000002', 'LOCALT-060-0000003']);
  });
});

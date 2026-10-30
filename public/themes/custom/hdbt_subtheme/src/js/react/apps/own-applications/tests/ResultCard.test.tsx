import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, test, vi } from 'vitest';
import ResultCard from '../components/results/ResultCard';
import { createApplication } from './testData';

const renderCard = (overrides = {}) => render(<ResultCard application={createApplication(overrides)} />);

const actionLabels = (container: HTMLElement) =>
  Array.from(container.querySelectorAll('.card__actions .hds-button__label')).map((label) => label.textContent);

describe('ResultCard', () => {
  test('renders a list item card with the application details', () => {
    const { container } = renderCard();
    const card = container.querySelector('.card');
    expect(card?.className).toContain('application-list__item');
    expect(card?.className).toContain('application-list__item--received');
    expect(container.querySelector('h3.card__title')?.textContent).toBe('Test application');
    expect(container.querySelector('.application-list__item--application-number')?.textContent).toBe(
      'LOCALT-060-0000001',
    );
    expect(container.querySelector('.application-list__item--submitted')?.textContent).toBe('2025-01-01T12:00:00');
    expect(container.textContent).toContain('Submission date');
  });

  test('renders view and print actions for sent applications', () => {
    const { container } = renderCard();
    expect(actionLabels(container)).toEqual(['View', 'Print']);
    expect(container.querySelector('.card__actions a')?.getAttribute('href')).toBe('/view/LOCALT-060-0000001');
  });

  test('renders edit and delete actions for drafts', () => {
    const { container } = renderCard({ status: 'DRAFT', statusLabel: 'Draft' });
    expect(actionLabels(container)).toEqual(['Edit', 'Delete']);
    expect(container.querySelector('.application-delete-link-LOCALT-060-0000001')).not.toBeNull();
    expect(container.textContent).toContain('Draft saved time');
  });

  test('skips actions without a link', () => {
    const { container } = renderCard({ links: { view: '/view' } });
    expect(actionLabels(container)).toEqual(['View']);
  });

  test('opens the delete confirmation dialog instead of following the link', () => {
    const { container } = renderCard({ status: 'DRAFT', statusLabel: 'Draft' });
    const link = container.querySelector('.application-delete-link-LOCALT-060-0000001') as HTMLAnchorElement;
    const notPrevented = fireEvent.click(link);

    expect(notPrevented).toBe(false);
    expect(screen.getByRole('dialog', { name: 'Delete application' })).toBeTruthy();
    expect(screen.getByText('Are you sure you want to delete the application?')).toBeTruthy();
  });

  test('closes the delete dialog when cancelled', () => {
    const { container } = renderCard({ status: 'DRAFT', statusLabel: 'Draft' });
    fireEvent.click(container.querySelector('.application-delete-link-LOCALT-060-0000001') as HTMLAnchorElement);
    fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));
    expect(screen.queryByRole('dialog')).toBeNull();
  });

  test('deletes the draft and shows an error when deleting fails', async () => {
    const fetchMock = vi.fn(async (url: string) =>
      url === '/session/token'
        ? new Response('token')
        : new Response(JSON.stringify({ redirectUrl: '' }), { headers: { 'Content-Type': 'application/json' } }),
    );
    const originalFetch = globalThis.fetch;
    globalThis.fetch = fetchMock as unknown as typeof fetch;

    const { container } = renderCard({ status: 'DRAFT', statusLabel: 'Draft' });
    fireEvent.click(container.querySelector('.application-delete-link-LOCALT-060-0000001') as HTMLAnchorElement);
    fireEvent.click(document.getElementById('own-applications__delete-draft-confirm') as HTMLElement);

    expect(await screen.findByText('Deleting application failed. Please try again or contact support.')).toBeTruthy();
    expect(fetchMock).toHaveBeenCalledWith('/delete/LOCALT-060-0000001', {
      method: 'POST',
      headers: { 'X-CSRF-Token': 'token' },
    });
    globalThis.fetch = originalFetch;
  });

  test('renders status, error and unread message tags', () => {
    const { container } = renderCard({
      status: 'DRAFT',
      statusLabel: 'Draft',
      errorType: 'NOT_OPEN',
      unreadMessages: '2 new messages',
    });
    const tags = Array.from(container.querySelectorAll('.card__category li')).map((tag) => tag.textContent);
    expect(tags).toEqual(['Draft', 'Application period ended', '2 new messages']);
  });

  test.each([
    ['RECEIVED', 'info', 'info-circle'],
    ['READY', 'success', 'check-circle'],
    ['REJECTED', 'error', 'error'],
  ])('adds an icon to the %s status tag', (status, color, icon) => {
    const { container } = renderCard({ status, statusLabel: status });
    const tag = container.querySelector(`.content-tags__tags__tag--${color}`);
    expect(tag?.querySelector('svg')?.getAttribute('aria-label')).toBe(icon);
  });

  test('adds no icon to the draft status tag', () => {
    const { container } = renderCard({ status: 'DRAFT', statusLabel: 'Draft' });
    expect(container.querySelector('.card__category li svg')).toBeNull();
  });

  test('adds an info icon to the archived form tag only', () => {
    const archived = renderCard({ status: 'DRAFT', statusLabel: 'Draft', errorType: 'ARCHIVED' });
    expect(archived.container.querySelector('.content-tags__tags__tag--alert svg')?.getAttribute('aria-label')).toBe(
      'info-circle',
    );
    archived.unmount();

    const notOpen = renderCard({ status: 'DRAFT', statusLabel: 'Draft', errorType: 'NOT_OPEN' });
    expect(notOpen.container.querySelector('.content-tags__tags__tag--alert svg')).toBeNull();
  });

  test('colors the status tag by status', () => {
    const { container } = renderCard({ status: 'READY', statusLabel: 'Ready' });
    expect(container.querySelector('.content-tags__tags__tag--success')).not.toBeNull();
  });
});

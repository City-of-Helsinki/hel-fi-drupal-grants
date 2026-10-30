import { fireEvent, render } from '@testing-library/react';
import { describe, expect, test, vi } from 'vitest';
import { ApplicationCard } from '../components/ApplicationCard';
import { createApplication } from './testData';

const renderCard = (overrides = {}) => render(<ApplicationCard application={createApplication(overrides)} />);

const actionLabels = (container: HTMLElement) =>
  Array.from(container.querySelectorAll('.card__actions .hds-button__label')).map((label) => label.textContent);

describe('ApplicationCard', () => {
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
    const createDialog = vi.fn();
    vi.stubGlobal('Drupal', { ...Drupal, dialogFunctions: { createDialog, removeDialog: vi.fn() } });

    const { container } = renderCard({ status: 'DRAFT', statusLabel: 'Draft' });
    const link = container.querySelector('.application-delete-link-LOCALT-060-0000001') as HTMLAnchorElement;
    const notPrevented = fireEvent.click(link);

    expect(notPrevented).toBe(false);
    expect(createDialog).toHaveBeenCalledTimes(1);
    expect(createDialog.mock.calls[0][0].dialogVariant).toBe('danger');
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

  test('colors the status tag by status', () => {
    const { container } = renderCard({ status: 'READY', statusLabel: 'Ready' });
    expect(container.querySelector('.content-tags__tags__tag--success')).not.toBeNull();
  });
});

import type { ApplicationItem } from '../types/ApplicationItem';

export const createApplication = (overrides: Partial<ApplicationItem> = {}): ApplicationItem => ({
  applicationNumber: 'LOCALT-060-0000001',
  errorType: null,
  formName: 'Test application',
  links: {
    delete: '/delete/LOCALT-060-0000001',
    edit: '/edit/LOCALT-060-0000001',
    print: '/print/LOCALT-060-0000001',
    view: '/view/LOCALT-060-0000001',
  },
  status: 'RECEIVED',
  statusLabel: 'Received',
  submitted: '01.01.2025 12:00',
  submittedSortable: '2025-01-01T12:00:00',
  unreadMessages: null,
  ...overrides,
});

export const createApplications = (count: number, overrides: Partial<ApplicationItem> = {}): ApplicationItem[] =>
  Array.from({ length: count }, (_, i) => {
    const number = `LOCALT-060-${String(i + 1).padStart(7, '0')}`;
    const day = String(i + 1).padStart(2, '0');
    return createApplication({
      applicationNumber: number,
      formName: `Application ${i + 1}`,
      submitted: `${day}.01.2025 12:00`,
      submittedSortable: `2025-01-${day}T12:00:00`,
      ...overrides,
    });
  });

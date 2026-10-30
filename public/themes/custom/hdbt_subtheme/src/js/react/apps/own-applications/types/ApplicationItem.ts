export type ApplicationItem = {
  applicationNumber: string;
  // Set when a draft can't be continued.
  errorType: 'NOT_OPEN' | 'ARCHIVED' | null;
  formName: string;
  links: {
    delete?: string | null;
    edit?: string | null;
    print?: string | null;
    view?: string | null;
  };
  status: string;
  statusLabel: string;
  // Formatted for display, e.g. 31.12.2025 12:00.
  submitted: string | null;
  // ISO 8601 date used for sorting, e.g. 2025-12-31T12:00:00.
  submittedSortable: string | null;
  unreadMessages: string | null;
};

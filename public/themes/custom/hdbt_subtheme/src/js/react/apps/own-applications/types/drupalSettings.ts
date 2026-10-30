declare namespace drupalSettings {
  const own_applications: {
    drafts: import('./ApplicationItem').ApplicationItem[];
    sent: import('./ApplicationItem').ApplicationItem[];
  };
}

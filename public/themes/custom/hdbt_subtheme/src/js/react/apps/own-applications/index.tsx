import React from 'react';
import { createRoot } from 'react-dom/client';
import initSentry from '@/react/common/helpers/Sentry';
import { DraftApplications } from './components/DraftApplications';
import { SentApplications } from './components/SentApplications';

initSentry();

const rootSelector: string = 'own-application-search';
const rootElement: HTMLElement | null = document.getElementById(rootSelector);

if (rootElement) {
  const { drafts = [], sent = [] } = drupalSettings.own_applications ?? {};

  createRoot(rootElement).render(
    <React.StrictMode>
      <DraftApplications applications={drafts} />
      <SentApplications applications={sent} />
    </React.StrictMode>,
  );
}

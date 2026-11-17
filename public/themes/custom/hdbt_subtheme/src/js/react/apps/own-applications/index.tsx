import React from 'react';
import { createRoot } from 'react-dom/client';
import initSentry from '@/react/common/helpers/Sentry';
import DraftsContainer from './containers/DraftsContainer';
import SentContainer from './containers/SentContainer';

initSentry();

const rootSelector: string = 'own-applications-search';
const rootElement: HTMLElement | null = document.getElementById(rootSelector);

if (rootElement) {
  const { drafts = [], sent = [] } = drupalSettings.own_applications ?? {};

  createRoot(rootElement).render(
    <React.StrictMode>
      <DraftsContainer applications={drafts} />
      <SentContainer applications={sent} />
    </React.StrictMode>,
  );
}

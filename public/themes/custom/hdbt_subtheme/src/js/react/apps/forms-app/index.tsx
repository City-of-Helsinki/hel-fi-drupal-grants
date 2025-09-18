import { ErrorBoundary } from '@sentry/react';
import React, { Suspense } from 'react';
import ReactDOM from 'react-dom';
import { LoadingSpinner } from 'hds-react';

import { FormNotFoundError } from './components/FormNotFoundError';
import { GeneralError } from './components/GeneralError';
import { ToastStack } from './components/ToastStack';
import initSentry from '@/react/common/helpers/Sentry';
import { AppContainer } from './containers/AppContainer';

initSentry();

const rootSelector: string = 'grants-react-form';
const rootElement: HTMLElement | null = document.getElementById(rootSelector);
const { application_number: applicationTypeId } = drupalSettings.grants_react_form;

const showError = false;

if (rootElement) {
  ReactDOM.render(
    <React.StrictMode>
      <ErrorBoundary
        fallback={<GeneralError />}
      >
        <ToastStack />
        <Suspense fallback={<LoadingSpinner />}>
          {showError
            ? <FormNotFoundError />
            : <AppContainer
                applicationTypeId={applicationTypeId}
                token={drupalSettings.grants_react_form.token}
              />
          }
        </Suspense>
      </ErrorBoundary>
    </React.StrictMode>,
    rootElement
  );
}

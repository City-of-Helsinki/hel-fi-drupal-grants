import { ErrorBoundary, type FallbackRender } from '@sentry/react';
import React, { Suspense } from 'react';
import ReactDOM from 'react-dom';
import { LoadingSpinner, Notification } from 'hds-react';

import { GeneralError } from './components/GeneralError';
import { ToastStack } from './components/ToastStack';
import initSentry from '@/react/common/helpers/Sentry';
import { AppContainer } from './containers/AppContainer';
import { InvalidSchemaError } from './errors/InvalidSchemaError';
import { BackendError } from './errors/BackendError';

initSentry();

const formatSchemaErrors = (errors: string) => (
  <div>
    <h3>Invalid schema</h3>
    <ul>
      {errors.split(',').map((error) => (
        <li key={error}>{error}</li>
      ))}
    </ul>
  </div>
);

const rootSelector: string = 'grants-react-form';
const rootElement: HTMLElement | null = document.getElementById(rootSelector);
const { application_number: applicationTypeId } =
  drupalSettings.grants_react_form;

// @todo Implement a better check
const isDevEnvironment = window.location.hostname.includes('docker.so');

const handleErrorFallback: FallbackRender = ({ error }) => {
  if (error instanceof InvalidSchemaError && isDevEnvironment) {
    return (
      <div style={{ backgroundColor: 'salmon', padding: '28px' }}>
        {formatSchemaErrors(error.message)}
      </div>
    );
  }

  if (error instanceof BackendError) {
    return (
      <Notification type='error' label={Drupal.t('Error')}>
        {error.message}
      </Notification>
    );
  }

  return <GeneralError />;
};

if (rootElement) {
  ReactDOM.render(
    <React.StrictMode>
      <ErrorBoundary fallback={handleErrorFallback}>
        <ToastStack />
        <Suspense fallback={<LoadingSpinner />}>
          <AppContainer
            applicationTypeId={applicationTypeId}
            token={drupalSettings.grants_react_form.token}
          />
        </Suspense>
      </ErrorBoundary>
    </React.StrictMode>,
    rootElement,
  );
}

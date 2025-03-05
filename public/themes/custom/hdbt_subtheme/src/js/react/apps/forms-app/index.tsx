import React from 'react';
import ReactDOM from 'react-dom';
import { ErrorBoundary } from '@sentry/react';

import initSentry from '@/react/common/helpers/Sentry';
import { FormNotFoundError } from './components/FormNotFoundError';
import FormWrapper from './containers/FormWrapper';
import { GeneralError } from './components/GeneralError';

initSentry();

const rootSelector: string = 'grants-react-form';
const rootElement: HTMLElement | null = document.getElementById(rootSelector);
const { application_number: applicationNumber } = drupalSettings.grants_react_form;

const showError = false;

if (rootElement) {
  ReactDOM.render(
    <React.StrictMode>
      <ErrorBoundary
        fallback={<GeneralError />}
      >
        {showError
          ? <FormNotFoundError />
          : <FormWrapper {...{applicationNumber}} />
        }
      </ErrorBoundary>
    </React.StrictMode>,
    rootElement
  );
}

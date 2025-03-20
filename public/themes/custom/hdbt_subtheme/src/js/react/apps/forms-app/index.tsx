import React from 'react';
import ReactDOM from 'react-dom';
import { ErrorBoundary } from '@sentry/react';

import initSentry from '@/react/common/helpers/Sentry';
import { FormNotFoundError } from './components/FormNotFoundError';
import FormWrapper from './containers/FormWrapper';
import { GeneralError } from './components/GeneralError';
import { ToastStack } from './components/ToastStack';

initSentry();

const rootSelector: string = 'grants-react-form';
const rootElement: HTMLElement | null = document.getElementById(rootSelector);
// @todo expand when more forms are available
// const { application_type_id: applicationTypeId } = drupalSettings.grants_react_form;
const applicationTypeId = '58';

const showError = false;

if (rootElement) {
  ReactDOM.render(
    <React.StrictMode>
      <ErrorBoundary
        fallback={<GeneralError />}
      >
        <ToastStack />
        {showError
          ? <FormNotFoundError />
          : <FormWrapper {...{applicationTypeId}} />
        }
      </ErrorBoundary>
    </React.StrictMode>,
    rootElement
  );
}

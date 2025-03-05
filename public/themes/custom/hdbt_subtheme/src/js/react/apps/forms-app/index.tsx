import React from 'react';
import ReactDOM from 'react-dom';

import initSentry from '@/react/common/helpers/Sentry';
import { FormNotFoundError } from './components/FormNotFoundError';
import FormWrapper from './containers/FormWrapper';

initSentry();

const rootSelector: string = 'grants-react-form';
const rootElement: HTMLElement | null = document.getElementById(rootSelector);
const { application_number: applicationNumber } = drupalSettings.grants_react_form;

const showError = false;

if (rootElement) {
  ReactDOM.render(
    <React.StrictMode>
      {showError
        ? <FormNotFoundError />
        : <FormWrapper {...{applicationNumber}} />
      }
    </React.StrictMode>,
    rootElement
  );
}

import useSWRImmutable from 'swr/immutable';
import { LoadingSpinner } from 'hds-react';
import i18next from 'i18next';
import { initReactI18next } from 'react-i18next';

import { FormWrapper } from './FormWrapper';
import { getUrlParts } from '../testutils/Helpers';
import { BackendError } from '../errors/BackendError';

/**
 * Instantiates a new application draft for the given form.
 *
 * @param {string} id - The form id.
 * @param {string} token - The CSRF token.
 *
 * @return {Promise<Object>} - The response from the server.
 *
 * @throws {Error} - If the instantiation request fails.
 */
const instantiateDocument = async (id: string, token: string) => {
  const response = await fetch(`/applications/${id}`, {
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
    method: 'POST',
  });

  if (!response.ok) {
    throw new Error('Failed to instantiate application');
  }

  return response.json();
};

/**
 * Handle errors from the server response.
 *
 * @param {Response} response - The fetch response object.
 * @throws {BackendError|Error} - Throws a BackendError if the response contains an error message,
 *                                otherwise throws a generic Error.
 */
const handleErrors = async (response: Response) => {
  const json = await response.json();

  if (json?.error) {
    throw new BackendError(json.error);
  }

  throw new Error('Failed to fetch form config data.');
};

/**
 * Fetcher function for the form data.
 * Queries form data from the server.
 * Checks IndexedDB for existing form data.
 *
 * @param {string} id - The form id
 * @param {string} token - CSRF token
 *
 * @return {Promise<object>} - Form settings and existing cached form data
 *
 * @throws {Error} - If the request fails
 */
async function fetchFormData(id: string, token: string) {
  let applicationNumber = getUrlParts()?.[4];

  if (!applicationNumber) {
    const { application_number } = await instantiateDocument(id, token);
    applicationNumber = application_number;
  }

  const { use_draft: useDraft } = drupalSettings.grants_react_form;
  // Uses DraftApplication or Application REST resource based on useDraft flag
  const fetchUrl = useDraft
    ? `/applications/${id}/${applicationNumber}`
    : `/applications/${id}/application/${applicationNumber}`;
  const formConfigResponse = await fetch(fetchUrl, {
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
  });

  if (!formConfigResponse.ok) {
    await handleErrors(formConfigResponse);
    return;
  }

  const formConfig = await formConfigResponse.json();
  const persistedData = { ...formConfig.form_data };

  return { ...formConfig, persistedData, applicationNumber };
}

export const AppContainer = ({
  applicationTypeId,
  token,
}: {
  applicationTypeId: string;
  token: string;
}) => {
  const { data, error, isLoading, isValidating } = useSWRImmutable(
    applicationTypeId,
    (id) => fetchFormData(id, token),
  );

  if (isLoading || isValidating) {
    return <LoadingSpinner />;
  }

  if (error) {
    throw error;
  }

  i18next.use(initReactI18next).init({
    // Enable for additional info. Don't use in prod.
    // debug: true,
    fallbackLng: 'fi',
    lng: drupalSettings.path.currentLanguage,
    resources: data?.translations,
    parseMissingKeyHandler: (_key: string) => null,
    returnNull: true,
  });

  return <FormWrapper {...{ applicationTypeId, token, data }} />;
};

const getPostHeaders = (token: string) => ({ 'Content-Type': 'application/json', 'X-CSRF-Token': token });

/**
 * Utility class for managing the different endpoints the app uses.
 */
export const Requests = {
  DRAFT_APPLICATION_CREATE: (form_identifier: string, token: string): Promise<Response> =>
    fetch(`/applications/${form_identifier}/draft`, {
      method: 'POST',
      headers: getPostHeaders(token),
    }),
};

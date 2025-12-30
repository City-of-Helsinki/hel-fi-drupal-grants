const getPostHeaders = (token: string) => ({
  'Content-Type': 'application/json',
  'X-CSRF-Token': token,
});

/**
 * Utility class for managing the different endpoints the app uses.
 */
export const Requests = {
  DRAFT_APPLICATION_CREATE: (
    id: string,
    token: string,
    copy: string | null = null,
  ): Promise<Response> =>
    fetch(`/applications/${id}/draft${copy ? `/${copy}` : ''}`, {
      method: 'POST',
      headers: getPostHeaders(token),
    }),
};

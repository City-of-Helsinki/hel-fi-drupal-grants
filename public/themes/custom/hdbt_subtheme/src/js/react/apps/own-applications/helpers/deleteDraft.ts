const fetchToken = async (): Promise<string> => {
  try {
    const response = await fetch('/session/token');
    return response.ok ? await response.text() : '';
  } catch (error) {
    console.error(error);
    return '';
  }
};

/**
 * Deletes a draft application.
 *
 * @param url - The delete URL of the draft.
 * @return The URL to redirect to, or an empty string if deleting failed.
 */
export const deleteDraft = async (url: string): Promise<string> => {
  const token = await fetchToken();
  if (!token) {
    return '';
  }

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-Token': token },
    });
    const data = await response.json();
    return data.redirectUrl ?? '';
  } catch (error) {
    console.error(error);
    return '';
  }
};

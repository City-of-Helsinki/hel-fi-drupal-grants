import { type APIRequestContext, expect, request as playwrightRequest } from '@playwright/test';

/**
 * Creates a short-lived HTTP client, makes one GET request, then
 * cleans up the client before returning the parsed response.
 *
 * Use this when you need to fetch data outside of a browser page,
 * for example when loading the form schema before a test starts.
 *
 * @param endpoint
 *   The URL path to request, e.g. '/fi/application/preview/123'.
 * @param params
 *   Optional query parameters to append to the URL.
 */
export async function fetchJsonApiRequest<T>(
  endpoint: string,
  params?: Record<string, string | number | boolean>,
): Promise<T> {
  console.log('endpoint',endpoint)

  // Create a new API context with the provided base URL.
  // @todo: Should we use a valid certificate instead of
  //        ignoring the certificate?
  const api = await playwrightRequest.newContext({
    ignoreHTTPSErrors: true,
  });

  // Make the request and ensure the API context is properly disposed.
  try {
    return await fetchRequest<T>(api, endpoint, params);
  } finally {
    await api.dispose();
  }
}

/**
 * Makes a GET request using an existing HTTP client and returns the
 * parsed JSON response. Fails the test if the request was not successful.
 *
 * @param request
 *   The Playwright API request context to use for the request.
 * @param endpoint
 *   The URL path to request.
 * @param params
 *   Optional query parameters to append to the URL.
 */
export async function fetchRequest<T>(
  request: APIRequestContext,
  endpoint: string,
  params?: Record<string, string | number | boolean>,
): Promise<T> {
  // Make the GET request and handle the response.
  const response = await request.get(endpoint, { params });

  // Verify the response was successful.
  const isOk = response.ok();
  expect(
    isOk,
    isOk ? undefined : `GET ${endpoint} failed with status ${response.status()} ${response.statusText()}`,
  ).toBeTruthy();

  // Parse and return the JSON response.
  return (await response.json()) as T;
}

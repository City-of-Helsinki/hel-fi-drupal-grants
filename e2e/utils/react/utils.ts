import {expect, type Page, test} from "@playwright/test";
import { type FormPreviewResponse } from './schemaFetcher';

/**
 * Returns a function that looks up translated text by key.
 *
 * Use the returned function anywhere you need a field label or message
 * in the correct language. If no translation exists for the key,
 * the key itself is returned so the test does not silently break.
 *
 * @param data
 *   The form data containing all translations.
 * @param language
 *   The language code to translate into. Defaults to 'fi'.
 */
export function createTranslator(data: FormPreviewResponse, language: string = 'fi'): (key: string) => string {
  const map = data.translations[language]?.translation ?? {};
  return (key: string) => map[key] ?? key;
}

/**
 * Verify the React application has been fully loaded.
 *
 * @param page
 *   The Playwright page instance.
 */
export async function waitForFormLoad(page: Page) {
  await test.step('Wait for React form to load', async () => {
    await page.waitForSelector('#grants-react-form .grants-form');
    await expect(page.locator('#grants-react-form .grants-form')).toBeVisible();
  });
}

/**
 * Listens for the server response that creates the draft application
 * and reads the application number from it.
 *
 * The number is assigned by the server the moment the form is opened
 * for the first time. We need it to build the correct form URL for
 * the rest of the test steps.
 *
 * @param page
 *   The Playwright page instance to attach the listener to.
 */
export const captureApplicationNumber = (page: Page): Promise<string> =>
  test.step('Capture application number from draft creation request', () =>
    new Promise<string>((resolve, reject) => {
      page.route(/\/applications\/.*\/draft/, async (route) => {
        if (route.request().method() !== 'POST') {
          return route.continue();
        }
        try {
          const response = await route.fetch();
          const json = await response.json();
          await route.fulfill({ response });
          await page.unroute(/\/applications\/.*\/draft/);
          resolve(json.application_number as string);
        } catch (err) {
          reject(err);
        }
      }).catch(reject);
    })
  );

import { expect, type Page, test } from "@playwright/test";
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

/**
 * Checks that the error summary notification at the top of the form
 * is visible and contains at least one missing-field item.
 *
 * Used after submitting the empty form to confirm that all required
 * fields produced an error message.
 *
 * @param page
 *   The Playwright page instance.
 */
export const assertMissingInputsVisible = (page: Page) =>
  test.step('Assert missing-input summary notification is visible', async () => {
    const missingInputItems = (page: Page) => page.locator('[class*="notification_hds-notification--error__"] li');
    await expect(missingInputItems(page).first()).toBeVisible();
  });

/**
 * Checks that the error summary notification is no longer visible.
 *
 * Used after filling all required fields to confirm that the form
 * no longer shows any missing-field errors.
 *
 * @param page
 *   The Playwright page instance.
 */
export const assertMissingInputsGone = (page: Page) =>
  test.step('Assert missing-input summary notification is gone', async () => {
    await expect(
      page.locator('[class*="notification_hds-notification--error__"]')
    ).not.toBeVisible();
  });

/**
 * Checks that the inline error message below a field is no longer
 * visible, confirming the field was filled with an accepted value.
 *
 * @param page
 *   The Playwright page instance.
 * @param fieldId
 *   The HTML id of the field whose error to check.
 */
export const assertFieldErrorGone = (page: Page, fieldId: string) =>
  test.step(`Assert inline error gone: ${fieldId}`, async () => {
    await expect(page.locator(`#${fieldId}-error`)).not.toBeVisible();
  });

/**
 * Clicks every step button in the stepper and returns to the first
 * step, triggering validation on every step along the way.
 *
 * This causes the form to show all required-field error messages at
 * once, so the test can check that every required field is flagged.
 *
 * @param page
 *   The Playwright page instance.
 */
export const gatherRequiredFieldWarnings = (page: Page) =>
  test.step('Click through all stepper buttons and return to first step', async () => {
    const stepper = page.locator('.hdbt-form--stepper');
    const buttons = stepper.getByRole('button');
    const count = await buttons.count();
    for (let i = 0; i < count; i++) {
      const button = buttons.nth(i);
      if (await button.isDisabled()) continue;
      await button.click();
    }
    await buttons.nth(0).click();
  });

/**
 * Clicks the stepper button at numeric position "i" to navigate to that step.
 *
 * @param page
 *   The Playwright page instance.
 * @param i
 *   The zero-based index of the step button to click.
 */
export async function clickOnStep(page: Page, i: number) {
  const buttons = page.locator('.hdbt-form--stepper').getByRole('button');
  await buttons.nth(i).click();
}

/**
 * Clicks the stepper button whose label matches the given translation
 * key.
 *
 * @param page
 *   The Playwright page instance.
 * @param t
 *   The translation function for the current language.
 * @param name
 *   The translation key for the step title, e.g. 'confirm_and_submit.title'.
 */
export async function clickOnStepWithTitle(page: Page, t: (key: string) => string, name: string) {
  const button = page.locator('.hdbt-form--stepper').getByRole('button', { name: t(name)});
  await expect(button).toBeVisible();
  await button.click();
}

/**
 * Waits until the React form is visible on the page.
 *
 * @param page
 *   The Playwright page instance.
 */
export async function waitForForm(page: Page) {
  await page.waitForSelector('#grants-react-form .grants-form');
  await expect(page.locator('#grants-react-form .grants-form')).toBeVisible();
}

/**
 * Clicks the "Save as draft" button and waits for the page to reload.
 *
 * @param page
 *   The Playwright page instance.
 * @param t
 *   The translation function used to find the button by its label.
 */
export async function saveDraft(page: Page, t: (key: string) => string) {
  const button = page.locator('.hdbt-form--actions').getByRole('button', { name: t('save_as_draft') });
  await expect(button).toBeVisible();
  await button.click();
  await page.waitForLoadState('domcontentloaded');
}

/**
 * Clicks the "Next" or "Preview" button to move to the next step.
 *
 * @param page
 *   The Playwright page instance.
 */
export async function clickNext(page: Page) {
  await test.step('Click Next', async () => {
    const formActions = page.locator('.hdbt-form--actions');
    const button = formActions.getByRole('button', {name: /Next|Seuraava|Nästa|Preview|Esikatseluun|För förhandsvisning/i});
    await expect(button).toBeVisible();
    await button.click();
  });
}

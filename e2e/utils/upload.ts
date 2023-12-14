import { Page, expect } from '@playwright/test';
import { PATH_TO_TEST_PDF } from './constants';

export const uploadFile = async (page: Page, selector: string, filePath: string = PATH_TO_TEST_PDF) => {
  const fileInput = page.locator(selector);
  const responsePromise = page.waitForResponse((r) => r.request().method() === 'POST', { timeout: 30 * 1000 });

  // FIXME: Use locator actions and web assertions that wait automatically
  await page.waitForTimeout(2000);

  await expect(fileInput).toBeAttached();
  await fileInput.setInputFiles(filePath);

  await page.waitForTimeout(2000);

  await expect(fileInput, 'File upload failed').toBeHidden();
  await responsePromise;
};

export const uploadBankConfirmationFile = async (page: Page, selector: string) => {
  const fileInput = page.locator(selector);
  const fileLink = page.locator('a[type="application/pdf"]');

  const responsePromise = page.waitForResponse(
    (r) => {
      if (r.request().method() !== 'POST') return false;

      if (r.ok()) {
        return true;
      } else {
        const errorMessage = ['POST request failed', r.status(), r.statusText()].join(' ');
        throw Error(errorMessage);
      }
    },
    { timeout: 15 * 1000 }
  );

  // FIXME: Use locator actions and web assertions that wait automatically
  await page.waitForTimeout(1000);

  await expect(fileInput).toBeAttached();
  await fileInput.setInputFiles(PATH_TO_TEST_PDF);
  await responsePromise;

  await page.waitForTimeout(1000);
  await expect(fileLink).toBeVisible();
};

import { Page, expect } from '@playwright/test';
import { PATH_TO_TEST_PDF } from './constants';

export const uploadFile = async (page: Page, inputSelector: string, filePath: string = PATH_TO_TEST_PDF) => {
  const fileInput = page.locator(inputSelector);

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
  await fileInput.setInputFiles(filePath);
  await responsePromise;

  await page.waitForTimeout(1000);
};

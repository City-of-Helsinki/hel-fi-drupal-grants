import { Page, expect } from '@playwright/test';
import { PATH_TO_TEST_PDF } from './constants';

export const uploadFile = async (page: Page, selector: string, filePath: string = PATH_TO_TEST_PDF) => {
  // FIXME: Use locator actions and web assertions that wait automatically
  await page.waitForTimeout(2000);

  const fileInput = page.locator(selector);

  const responsePromise = page.waitForResponse((res) => res.request().method() === 'POST' && res.ok(), { timeout: 60 * 1000 });

  await fileInput.setInputFiles(filePath);

  await page.waitForTimeout(2000);

  await expect(fileInput, 'File upload failed').toBeHidden();
  await responsePromise;
};

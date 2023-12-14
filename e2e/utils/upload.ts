import { Locator, Page, expect } from '@playwright/test';
import { PATH_TO_TEST_PDF } from './constants';

export const uploadFile = async (page: Page, locator: Locator, filePath: string = PATH_TO_TEST_PDF) => {
  const spinner = page.locator('.hds-loading-spinner').last();
  await expect(spinner).toBeHidden({ timeout: 15 * 1000 });

  const reqPromise = page.waitForRequest((req) => {
    if (req.method() === 'POST') {
      console.log('POST', req.url());
      return true;
    }
    return false;
  });

  const fileChooserPromise = page.waitForEvent('filechooser');
  await locator.click({ force: true });
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles(filePath);
  const req = await reqPromise;

  await page.waitForTimeout(5000);
  const res = await req.response();
  expect(res?.statusText(), 'POST response should be successful').toBe('OK');
};

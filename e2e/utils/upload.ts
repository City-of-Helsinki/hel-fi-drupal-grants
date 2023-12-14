import { Locator, Page, expect } from '@playwright/test';
import { PATH_TO_TEST_PDF } from './constants';

export const uploadFile = async (page: Page, locator: Locator, filePath: string = PATH_TO_TEST_PDF) => {
  const spinner = page.locator('.hds-loading-spinner').last();
  await expect(spinner).toBeHidden({ timeout: 15 * 1000 });

  const reqPromise = page.waitForRequest((req) => {
    if (req.method() === 'POST') {
      console.log('---- POST REQUEST', req.url());
      return true;
    }
    return false;
  });

  const fileChooserPromise = page.waitForEvent('filechooser');
  await locator.click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles(filePath);

  await expect
    .poll(
      async () => {
        const req = await reqPromise;
        const res = await req.response();

        console.log(res?.status() || 'waiting?');

        if (!res) return false;
        if (res.ok()) return true;

        const errorMessage = [res.status(), res.statusText()].join(' ');
        throw Error(errorMessage);
      },
      { message: 'POST response should be successful', timeout: 30_000 }
    )
    .toBeTruthy();

  await page.waitForTimeout(2000);
};

import { Locator, Page, expect } from '@playwright/test';
import { PATH_TO_TEST_PDF } from './constants';

export const uploadFile = async (page: Page, locator: Locator, filePath: string = PATH_TO_TEST_PDF) => {
  const spinner = page.locator('.hds-loading-spinner').last();
  await expect(spinner).toBeHidden({ timeout: 15 * 1000 });

  const responsePromise = page.waitForResponse((res) => {
    console.log(res.url());
    const postRequest = res.request().method() == 'POST';

    if (!postRequest) return false;
    if (!res.ok) throw Error(`File upload POST request returned ${res.status()}`);
    return true;
  });

  const fileChooserPromise = page.waitForEvent('filechooser');
  await locator.click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles(filePath);

  await responsePromise;
};

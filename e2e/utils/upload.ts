import { Locator, Page } from '@playwright/test';
import { PATH_TO_TEST_PDF } from './constants';

export const uploadFile = async (page: Page, locator: Locator, filePath: string = PATH_TO_TEST_PDF) => {
  const responsePromise = page.waitForResponse(
    (res) => {
      const postRequest = res.request().method() == 'POST';

      if (!postRequest) return false;
      if (!res.ok) throw Error(`File upload POST request returned ${res.status()}`);
      return true;
    },
    { timeout: 60 * 1000 }
  );

  const fileChooserPromise = page.waitForEvent('filechooser');
  await locator.click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles(filePath);

  await responsePromise;
};

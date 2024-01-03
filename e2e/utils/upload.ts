import { Locator, Page, expect } from '@playwright/test';
import { PATH_TO_TEST_PDF } from './constants';

export const uploadFile = async (page: Page, uploadButtonLocator: Locator, filePath: string = PATH_TO_TEST_PDF) => {
  const responsePromise = page.waitForResponse((r) => {
    if (r.request().method() !== 'POST') return false;

    if (r.ok()) {
      return true;
    } else {
      const errorMessage = ['POST request failed', r.status(), r.statusText()].join(' ');
      throw Error(errorMessage);
    }
  });

  const fileChooserPromise = page.waitForEvent('filechooser');
  await uploadButtonLocator.click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles(filePath);
  await responsePromise;

  await expect(page.getByText('Tiedoston lataaminen epäonnistui')).toBeHidden();
};

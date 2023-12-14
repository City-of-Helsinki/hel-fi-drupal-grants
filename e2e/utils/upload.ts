import { Locator, Page, expect } from '@playwright/test';
import { PATH_TO_TEST_PDF } from './constants';

export const uploadFile = async (page: Page, locator: Locator, filePath: string = PATH_TO_TEST_PDF) => {
  const spinner = page.locator('.hds-loading-spinner').last();
  await expect(spinner).toBeHidden({ timeout: 15 * 1000 });

  // const responsePromise = page.waitForResponse((res) => {
  //   console.log(res.url());
  //   const postRequest = res.request().method() === 'POST';

  //   if (!postRequest) return false;
  //   if (!res.ok) throw Error(`File upload POST request returned ${res.status()}`);
  //   console.log('---- POST RESPONSE OK', res.status(), res.url());
  //   return true;
  // });

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
  const req = await reqPromise;

  const resToReq = await req.response();
  // if (!resToReq) throw Error('no response gotten');
  expect(resToReq, "POST response should be successfull").toBeTruthy();
  console.log("Response status:", resToReq?.status())
  // await expect(req.response())

  // await responsePromise;
  await page.waitForTimeout(2000);
};

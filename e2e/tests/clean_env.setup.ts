import { expect, test as setup} from '@playwright/test';
const APP_ENV = getAppEnvForATV();

import {
  ATV_BASE_URL,
  ATV_API_KEY,
  getAppEnvForATV,
} from "../utils/document_helpers";

/**
 * Setup environment. So far only for env variables, can be extended in the future.
 */
setup('Setup environment', async () => {
  expect(ATV_API_KEY).toBeTruthy()
  expect(ATV_BASE_URL).toBeTruthy()
  expect(APP_ENV).toBeTruthy()
  expect(APP_ENV.toUpperCase()).not.toContain("PROD");
})




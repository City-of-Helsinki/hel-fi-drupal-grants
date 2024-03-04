import { chromium, type FullConfig } from '@playwright/test';
import {setDebugMode} from "../utils/debugging_helpers";
import {setDisabledFormVariants} from "../utils/form_variant_helpers";

/**
 * This function serves as the initial entry point when executing tests with Playwright.
 * It is designed to perform preliminary operations such as reading environment variables
 * or modifying test configurations before any test initialization occurs.
 *
 * While certain initializations can alternatively be managed through dependencies,
 * modifying test configurations or similar tasks via dependencies may not work,
 * as Playwright might consider it too late in the test lifecycle, leading to errors.
 * This function ensures such preparatory tasks are executed at the appropriate time.
 *
 * @docs: https://playwright.dev/docs/test-global-setup-teardown.
 *
 * @param config The full configuration object provided by Playwright.
 */
module.exports = async (config: FullConfig) => {
  setDebugMode();
  setDisabledFormVariants();
};

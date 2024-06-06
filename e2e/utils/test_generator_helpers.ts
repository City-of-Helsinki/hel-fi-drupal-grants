import {Browser, Page} from "@playwright/test";
import {FormData, PageHandlers} from "./data/test_data";
import {selectRole} from "./auth_helpers";
import {logger} from "./logger";
import {fillGrantsFormPage} from "./form_helpers";
import {getObjectFromEnv} from "./env_helpers";
import {copyApplication} from "./copying_helpers";
import {swapFieldValues} from "./field_swap_helpers";
import {validatePrintPage, validateSubmission} from "./validation_helpers";
import {deleteDraftApplication} from "./deletion_helpers";
import {verifyDraftButton} from "./verify_draft_button_helpers";

/**
 * The generateTests function.
 *
 * This function generates tests for a given profile type,
 * form ID, form pages, and test data array.
 *
 * @param profileType
 *   The profile type to use for the tests.
 * @param formId
 *   The form ID to use for the tests.
 * @param formPages
 *   The form pages to use for the tests.
 * @param testDataArray
 *   The test data array to use for the tests.
 *
 * @return
 *   An array of generated tests containing test name and test function.
 */
const generateTests = (
  profileType: string,
  formId: string,
  formPages: PageHandlers,
  testDataArray: [string, FormData][]) => {

  const tests = [];

  for (const [key, obj] of testDataArray) {

    // All form variants do the form filling test.
    tests.push({
      testName: `Form: ${obj.title}`,
      testFunction: async (page: Page) => {
        await fillGrantsFormPage(key, page, obj, obj.formPath, obj.formSelector, formId, profileType, formPages);
      }
    });

    // Application copying tests.
    if (obj.testFormCopying) {
      tests.push({
        testName: `Copy form: ${obj.title}`,
        testFunction: async (page: Page) => {
          const storedata = getObjectFromEnv(profileType, formId);
          await copyApplication(key, profileType, formId, page, obj, storedata);
        }
      });
    }

    // Field value swapping tests.
    if (obj.testFieldSwap) {
      tests.push({
        testName: `Field swap: ${obj.title}`,
        testFunction: async (page: Page) => {
          const storedata = getObjectFromEnv(profileType, formId);
          await swapFieldValues(key, page, obj, storedata);
        }
      });
    }

    // Verify draft button on for the draft variant.
    if (key === 'draft') {
      tests.push({
        testName: `Verify draft button: ${obj.title}`,
        testFunction: async (page: Page) => {
          const storedata = getObjectFromEnv(profileType, formId);
          await verifyDraftButton(key, page, obj, storedata);
        }
      });
    }

    // Validation tests.
    if (!(obj.viewPageSkipValidation || obj.testFormCopying || obj.testFieldSwap)) {
      tests.push({
        testName: `Validate: ${obj.title}`,
        testFunction: async (page: Page) => {
          const storedata = getObjectFromEnv(profileType, formId);
          await validateSubmission(key, page, obj, storedata);
        }
      });
    }

    // Print page validation tests.
    if (obj.validatePrintPage) {
      tests.push({
        testName: `Validate print page: ${obj.title}`,
        testFunction: async (page: Page, browser: Browser) => {
          logger('Creating new browser context with disabled JS...');

          // Create a new browser context with disabled JS to prevent the print call from happening
          // when we visit the print page (Playwright can't handle the print dialog).
          const JSDisabledContext = await browser.newContext({ javaScriptEnabled: false });
          const JSDisabledPage = await JSDisabledContext.newPage();
          await selectRole(JSDisabledPage, 'REGISTERED_COMMUNITY');

          const storedata = getObjectFromEnv(profileType, formId);
          await validatePrintPage(key, JSDisabledPage, obj, storedata);

          await JSDisabledPage.close();
          await JSDisabledContext.close();
        }
      });
    }

    // Deletion tests for all except the success variant.
    if (key !== 'success') {
      tests.push({
        testName: `Delete drafts: ${obj.title}`,
        testFunction: async (page: Page) => {
          const storedata = getObjectFromEnv(profileType, formId);
          await deleteDraftApplication(key, page, obj, storedata);
        }
      });
    }
  }

  return tests;
};

export {
  generateTests,
}

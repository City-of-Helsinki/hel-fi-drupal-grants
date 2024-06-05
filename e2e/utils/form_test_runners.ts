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

const generateTests = (
  profileType: string,
  formId: string,
  formPages: PageHandlers,
  testDataArray: [string, FormData][]) => {
  return [

    ...testDataArray
      .map(([key, obj]) => ({
        name: `Form: ${obj.title}`,
        fn: async (page: Page) => {
          await fillGrantsFormPage(
            key,
            page,
            obj,
            obj.formPath,
            obj.formSelector,
            formId,
            profileType,
            formPages
          );
        }
    })),

    ...testDataArray
      .filter(([_, obj]) => obj.testFormCopying)
      .map(([key, obj]) => ({
        name: `Copy form: ${obj.title}`,
        fn: async (page: Page) => {
          const storedata = getObjectFromEnv(profileType, formId);
          await copyApplication(
            key,
            profileType,
            formId,
            page,
            obj,
            storedata
          );
        }
      })),

    ...testDataArray
      .filter(([_, obj]) => obj.testFieldSwap)
      .map(([key, obj]) => ({
        name: `Field swap: ${obj.title}`,
        fn: async (page: Page) => {
          const storedata = getObjectFromEnv(profileType, formId);
          await swapFieldValues(
            key,
            page,
            obj,
            storedata
          );
        }
      })),

    ...testDataArray
      .filter(([_, obj]) => !(obj.viewPageSkipValidation || obj.testFormCopying || obj.testFieldSwap))
      .map(([key, obj]) => ({
        name: `Validate: ${obj.title}`,
        fn: async (page: Page) => {
          const storedata = getObjectFromEnv(profileType, formId);
          await validateSubmission(
            key,
            page,
            obj,
            storedata
          );
        }
      })),

    ...testDataArray
      .filter(([_, obj]) => obj.validatePrintPage)
      .map(([key, obj]) => ({
        name: `Validate print page: ${obj.title}`,
        fn: async (page: Page, browser: Browser) => {
          logger('Creating new browser context with disabled JS...');

          const JSDisabledContext = await browser.newContext({ javaScriptEnabled: false });
          const JSDisabledPage = await JSDisabledContext.newPage();
          await selectRole(JSDisabledPage, 'REGISTERED_COMMUNITY');

          const storedata = getObjectFromEnv(profileType, formId);
          await validatePrintPage(
            key,
            JSDisabledPage,
            obj,
            storedata
          );

          await JSDisabledPage.close();
          await JSDisabledContext.close();
        }
      })),

    ...testDataArray.map(([key, obj]) => ({
      name: `Delete drafts: ${obj.title}`,
      fn: async (page: Page) => {
        const storedata = getObjectFromEnv(profileType, formId);
        await deleteDraftApplication(
          key,
          page,
          obj,
          storedata
        );
      }
    }))
  ];
}

export {
  generateTests,
}

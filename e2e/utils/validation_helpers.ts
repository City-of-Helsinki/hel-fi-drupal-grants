import {Locator, Page, expect} from "@playwright/test";
import {logger} from "./logger";
import {
    FormField,
    MultiValueField,
    FormData,
    Selector,
    isMultiValueField,
    DynamicMultiValueField,
    isDynamicMultiValueField
} from "./data/test_data"


const validateSubmission = async (formKey: string, page: Page, formDetails: FormData, storedata: Object) => {

    // @ts-ignore
    const thisStoreData = storedata[formKey];


    if (thisStoreData.status === 'DRAFT') {
        await validateDraft(page, formDetails.formPages, thisStoreData);
    } else {
        await validateSent(page, formDetails.formPages, thisStoreData);
    }
}

/**
 * Validate submitted application. Maybe send messages etc.
 *
 * @param page
 * @param formPages
 * @param thisStoreData
 */
const validateSent = async (page: Page, formPages: Object, thisStoreData: Object) => {
    logger('Validate RECEIVED', thisStoreData);
}

/**
 * Validate application draft view page.
 *
 * @param page
 * @param formPages
 * @param thisStoreData
 */
const validateDraft = async (page: Page, formPages: Object, thisStoreData: Object) => {

    logger('Validate DRAFT', thisStoreData);

}


export {
    validateSubmission
}

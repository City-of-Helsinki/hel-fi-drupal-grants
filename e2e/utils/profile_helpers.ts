import {expect, Page,} from '@playwright/test';
import {logger} from "./logger";
import {FormData} from "./data/test_data"
import {deleteGrantsProfiles, fetchLatestProfileByType} from "./document_helpers";
import {fillProfileForm} from "./form_helpers";

/**
 * The function isTimestampLessThanAnHourAgo.
 *
 * @param timestamp
 */
function isTimestampLessThanAnHourAgo(timestamp: string) {
  const oneHourInMilliseconds = 60 * 60 * 1000; // 1 hour in milliseconds.
  const currentTimestamp = new Date().getTime();
  const targetTimestamp = new Date(timestamp).getTime();
  return currentTimestamp - targetTimestamp < oneHourInMilliseconds;
}

/**
 * Try to check if profile is just created so that
 * we can skip these, when running multiple test runs.
 *
 * @param profileType
 *  Profile type, registered_community, private_person etc..
 */
const isProfileCreated = async (profileType: string) => {
  logger('Profile...');

  if (process.env.CREATE_PROFILE === 'TRUE') {
    logger('... creation is forced through variable.');
    return false;
  }

  return fetchLatestProfileByType(process.env.TEST_USER_UUID ?? '', profileType)
    .then((profile) => {
      if (!profile || !profile.updated_at) return false;

      const {updated_at, content} = profile;
      const isLessThanHourAgo = isTimestampLessThanAnHourAgo(updated_at);

      if (!content.bankAccounts.length || content.bankAccounts.length < 1) {
        logger('...has missing content. Re-creating.');
        return false;
      }

      if (isLessThanHourAgo) {
        logger('...created less than an hour ago.');
        return true;
      }

      logger('...created more than hour ago and should be re-tested.');
      return false;

    })
    .catch((error) => {
      logger('Error fetching profile:', error);
      return false;
    });
};

/**
 * The runProfileFormTest function.
 *
 * This function runs profile form tests by:
 * 1. Deleting any existing profiles before a new test is executed.
 * 2. Filling a profile form with the given test data.
 *
 * @param page
 *   Playwright page object.
 * @param formData
 *   The form data we are testing.
 * @param profileType
 *   The profile type.
 */
const runProfileFormTest = async (page: Page, formData: FormData, profileType: string) => {
  await deleteGrantsProfiles(process.env.TEST_USER_UUID ?? '', profileType);
  await fillProfileForm(page, formData, formData.formPath, formData.formSelector);
};

export {
  isProfileCreated,
  runProfileFormTest
}

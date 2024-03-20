import {Page, test} from '@playwright/test';
import {logger} from "../../utils/logger";
import {fillProfileForm} from '../../utils/form_helpers'
import {isProfileCreated} from '../../utils/profile_helpers';
import {deleteGrantsProfiles} from "../../utils/document_helpers";
import {selectRole} from "../../utils/auth_helpers";
import {validateProfileData} from "../../utils/validation_helpers";
import {
  profileDataRegisteredCommunity as profileData,
  FormData
} from '../../utils/data/test_data'

const profileType = 'registered_community';

test.describe('Registered Community - Grants Profile', () => {
  let page: Page;
  let skip: boolean;
  const testDataArray: [string, FormData][] = Object.entries(profileData);

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    await selectRole(page, 'REGISTERED_COMMUNITY');

    skip = await isProfileCreated(profileType);
    if (skip) {
      process.env[`profile_created_${profileType}`] = 'TRUE';
    }
  });

  test.beforeEach(async () => {
    test.skip(skip);
  });

  test.afterAll(() => {
    if (process.env[`profile_created_${profileType}`] !== undefined &&
        process.env[`profile_created_${profileType}`] === 'TRUE') {
      logger(`Profile exists for: ${profileType}`);
    } else {
      logger(`Profile does not exist for: ${profileType}`);
    }
  });

  test('Profile creation', async () => {
    let successTest: FormData | null = null;

    for (const [key, obj] of testDataArray) {
      if (key === 'success') {
        successTest = obj;
        continue;
      }
      await deleteGrantsProfiles(process.env.TEST_USER_UUID ?? '', profileType);
      await fillProfileForm(page, obj, obj.formPath, obj.formSelector);
    }
    // Run the success test as the last test.
    if (successTest) {
      await deleteGrantsProfiles(process.env.TEST_USER_UUID ?? '', profileType);
      await fillProfileForm(page, successTest, successTest.formPath, successTest.formSelector);
    }
  });

  test('Test Grants profile data', async () => {
    for (const [key, obj] of testDataArray) {
      if (obj.viewPageSkipValidation) continue;
      await validateProfileData(
        page,
        obj,
        key
      );
    }
    process.env[`profile_created_${profileType}`] = 'TRUE';
  });

});



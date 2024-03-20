import {Page, test} from '@playwright/test';
import {logger} from "../../utils/logger";
import {fillProfileForm} from '../../utils/form_helpers'
import {isProfileCreated} from '../../utils/profile_helpers';
import {deleteGrantsProfiles} from "../../utils/document_helpers";
import {selectRole} from "../../utils/auth_helpers";
import {validateHardCodedProfileData, validateProfileData} from "../../utils/validation_helpers";
import {
  profileDataRegisteredCommunity as profileData,
  FormData
} from '../../utils/data/test_data'

const profileType = 'registered_community';

test.describe('Registered Community - Grants Profile', () => {
  let page: Page;
  let profileExists: boolean;
  let skipHardCodedDataTest: boolean = false;
  const testDataArray: [string, FormData][] = Object.entries(profileData);

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    await selectRole(page, 'REGISTERED_COMMUNITY');
    profileExists = await isProfileCreated(profileType);
  });

  test.afterAll(() => {
    if (process.env[`profile_exists_${profileType}`] !== undefined &&
        process.env[`profile_exists_${profileType}`] === 'TRUE') {
      logger(`Profile exists for: ${profileType}`);
    } else {
      logger(`Profile does not exist for: ${profileType}`);
    }
  });

  test('Profile creation', async () => {
    if (profileExists) {
      logger('Skipping profile creation test because profile already exists.');
      test.skip(profileExists);
    }

    logger('Profile creation test.')
    let successTest: FormData | null = null;

    for (const [key, obj] of testDataArray) {
      if (key === 'success') {
        successTest = obj;
        continue;
      }
      await deleteGrantsProfiles(process.env.TEST_USER_UUID ?? '', profileType);
      await fillProfileForm(page, obj, obj.formPath, obj.formSelector);
    }
    // Finally, fill the success test form.
    if (successTest) {
      await deleteGrantsProfiles(process.env.TEST_USER_UUID ?? '', profileType);
      await fillProfileForm(page, successTest, successTest.formPath, successTest.formSelector);
    }
  });

  test('Test Grants profile data', async () => {
    if (profileExists) {
      logger('Skipping profile data validation test because profile already exists.');
      test.skip(profileExists);
    }
    for (const [key, obj] of testDataArray) {
      if (obj.viewPageSkipValidation) continue;
      await validateProfileData(
        page,
        obj,
        key,
      );
    }
    skipHardCodedDataTest = true;
    process.env[`profile_exists_${profileType}`] = 'TRUE';
  });

  test('Test hard-coded Grants profile data', async () => {
    if (skipHardCodedDataTest) {
      logger('Skipping hard-coded profile data validation test because the data has already been validated.');
      test.skip(skipHardCodedDataTest);
    }
    await validateHardCodedProfileData(page, profileType);
    process.env[`profile_exists_${profileType}`] = 'TRUE';
  });

});



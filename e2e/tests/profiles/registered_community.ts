import {expect, Page, test} from '@playwright/test';
import {logger} from "../../utils/logger";
import {runProfileFormTest, isProfileCreated} from '../../utils/profile_helpers';
import {selectRole} from "../../utils/auth_helpers";
import {validateExistingProfileData, validateProfileData} from "../../utils/validation_helpers";
import {profileDataRegisteredCommunity as profileData, FormData} from '../../utils/data/test_data'

test.describe('Registered Community - Grants Profile', () => {
  let page: Page;
  let profileExists: boolean;
  let validateExistingProfile: boolean = false;
  const testDataArray: [string, FormData][] = Object.entries(profileData);
  const profileType = 'registered_community';

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    await selectRole(page, 'REGISTERED_COMMUNITY');
    profileExists = await isProfileCreated(profileType);
  });

  test.afterAll(async() => {
    expect(process.env[`profile_exists_${profileType}`], `Profile does not exist for: ${profileType}`).toBe('TRUE');
    logger(`Profile exist for: ${profileType}`);
    await page.close();
  });

  test('Profile form tests', async () => {
    if (profileExists) {
      logger('Profile already exists, skipping test.');
      test.skip(profileExists);
    }

    logger('Running profile form tests.')
    for (const [key, formData] of testDataArray) {
      if (key === 'success') continue;
      await runProfileFormTest(page, formData, profileType);
    }

    const successTestFormData = testDataArray.find(([key]) => key === 'success')?.[1];
    if (successTestFormData) await runProfileFormTest(page, successTestFormData, profileType);
  });

  test('Validate profile data', async () => {
    if (profileExists) {
      logger('Profile already exists, skipping test.');
      test.skip(profileExists);
    }

    logger('Validating profile form data.')
    for (const [key, obj] of testDataArray) {
      if (obj.viewPageSkipValidation) continue;
      await validateProfileData(page, obj, key, profileType);
    }

    // Since profile data was just validated, there is no need to validate an exciting profile.
    validateExistingProfile = true;
    process.env[`profile_exists_${profileType}`] = 'TRUE';
  });

  test('Validate existing profile', async () => {
    if (validateExistingProfile) {
      logger('Data already validated, skipping test.');
      test.skip(validateExistingProfile);
    }

    logger('Validating existing profile data.')
    await validateExistingProfileData(page, profileType);
    process.env[`profile_exists_${profileType}`] = 'TRUE';
  });

});


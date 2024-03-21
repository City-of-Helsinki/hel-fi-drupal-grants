import {expect, Page, test} from '@playwright/test';
import {logger} from "../../utils/logger";
import {runProfileFormTest, isProfileCreated} from '../../utils/profile_helpers';
import {selectRole} from "../../utils/auth_helpers";
import {validateHardCodedProfileData, validateProfileData} from "../../utils/validation_helpers";
import {profileDataUnregisteredCommunity as profileData, FormData} from '../../utils/data/test_data'

test.describe('Unregistered Community - Grants Profile', () => {
  let page: Page;
  let profileExists: boolean;
  let skipHardCodedDataTest: boolean = false;
  const testDataArray: [string, FormData][] = Object.entries(profileData);
  const profileType = 'unregistered_community';

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    await selectRole(page, 'UNREGISTERED_COMMUNITY', 'new');
    profileExists = await isProfileCreated(profileType);
  });

  test.afterAll(() => {
    expect(process.env[`profile_exists_${profileType}`], `Profile does not exist for: ${profileType}`).toBe('TRUE');
    logger(`Profile exist for: ${profileType}`);
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

    // Since profile data was just validated, no need to validate hard-coded data.
    skipHardCodedDataTest = true;
    process.env[`profile_exists_${profileType}`] = 'TRUE';
  });

  test('Validate hard-coded profile data', async () => {
    if (skipHardCodedDataTest) {
      logger('Data already validated, skipping test.');
      test.skip(skipHardCodedDataTest);
    }

    logger('Validating hard-coded profile form data.')
    await validateHardCodedProfileData(page, profileType);
    process.env[`profile_exists_${profileType}`] = 'TRUE';
  });

});



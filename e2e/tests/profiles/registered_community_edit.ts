import {Page, test} from '@playwright/test';
import {logger} from '../../utils/logger';
import {selectRole} from '../../utils/auth_helpers';
import {
  editProfileFields,
  isProfileResetEnabled,
  resetProfileFields,
  revertProfileFields
} from '../../utils/profile_edit_helpers';
import {PROFILE_EDIT_DATA} from '../../utils/data/profile_edit_data';

test.describe('Registered Community - Edit grants profile', () => {
  let page: Page;
  let originalValues: Record<string, string> = {};
  const profileType = 'registered_community';
  const editFields = PROFILE_EDIT_DATA[profileType];

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage();
    await selectRole(page, 'REGISTERED_COMMUNITY');
  });

  test.afterAll(async () => {
    await page.close();
  });

  test('Reset profile data', async () => {
    if (!isProfileResetEnabled()) {
      logger('Profile reset is not enabled, skipping test.');
      test.skip(true, 'Skip profile reset test.');
    }

    await resetProfileFields(page, profileType);
  });

  test('Edit and save profile data', async () => {
    if (isProfileResetEnabled()) {
      logger('Profile reset is enabled, skipping test.');
      test.skip(true, 'Skip profile edit test.');
    }

    originalValues = await editProfileFields(page, profileType, editFields);
  });

  test('Revert and save profile data', async () => {
    if (!Object.keys(originalValues).length) {
      logger('No original profile data stored, skipping test.');
      test.skip(true, 'Skip profile revert test.');
    }

    await revertProfileFields(page, profileType, originalValues);
  });

});

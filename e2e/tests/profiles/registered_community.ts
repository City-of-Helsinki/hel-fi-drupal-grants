import {Page, test} from '@playwright/test';
import {logger} from "../../utils/logger";
import {fillProfileForm} from '../../utils/form_helpers'
import {isProfileCreated} from '../../utils/profile_helpers';
import {deleteGrantsProfiles} from "../../utils/document_helpers";
import {selectRole} from "../../utils/auth_helpers";
import {
  profileDataRegisteredCommunity as profileData,
  FormData
} from '../../utils/data/test_data'

const profileVariableName = 'profileCreatedRegistered';
const profileType = 'registered_community';
let passedProfileCreationTest: boolean = false;

test.describe('Registered Community - Grants Profile', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    await selectRole(page, 'REGISTERED_COMMUNITY');
  });

  test.beforeEach(async () => {
    const skip = await isProfileCreated(profileVariableName, profileType);
    test.skip(skip);
  });

  test.afterAll(() => {
    if (passedProfileCreationTest) {
      logger('Profile created for: Registered community.');
      process.env.profileExistsRegistered = 'TRUE';
    } else {
      logger('There were failed tests for: Registered community.');
      process.env.profileExistsRegistered = 'FALSE';
    }
  });

  test('Profile creation', async () => {
    const testDataArray: [string, FormData][] = Object.entries(profileData);
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
    passedProfileCreationTest = true;
  });

  test('Test Grants profile data', async () => {
    test.fixme(true,'Feature not implemented.');
  });

});



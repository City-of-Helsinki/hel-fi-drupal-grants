import {
  expect,
  Page,
  PlaywrightTestArgs,
  PlaywrightTestOptions,
  PlaywrightWorkerArgs,
  PlaywrightWorkerOptions,
  test,
  TestInfo
} from '@playwright/test';

import {FormData, TEST_USER_UUID} from "./data/test_data"

import {fetchLatestProfileByType} from "./document_helpers";

// const isProfileCreated = (profileVariable: string, profileType: string) => {
//   console.log('isProfileCreated', process.env[profileVariable]);
//
//   const isCreatedThisTime = process.env[profileVariable] === 'TRUE';
//
//   if (!isCreatedThisTime) {
//     const profile = fetchLatestProfileByType(TEST_USER_UUID, profileType);
//     console.log('PROFILES', profile)
//   }
//
//   return isCreatedThisTime;
// }


function isTimestampLessThanAnHourAgo(timestamp: string) {
  const oneHourInMilliseconds = 60 * 60 * 1000; // 1 hour in milliseconds
  const currentTimestamp = new Date().getTime();
  const targetTimestamp = new Date(timestamp).getTime();

  return currentTimestamp - targetTimestamp < oneHourInMilliseconds;
}

/**
 * Try to check if profile is just created so we can skip these, when running
 * multiple test runs.
 *
 * DOES NOT WORK AS INTENDED!
 *
 * @param profileVariable
 * @param profileType
 */
const isProfileCreated = async (profileVariable: string, profileType: string) => {

  const isCreatedThisTime = process.env[profileVariable] === 'TRUE';
  const varname = 'fetchedProfile_' + profileType;
  const profileDoesNotExists = process.env[varname] === undefined;

  if (process.env.CREATE_PROFILE === 'true') {
    // No need to wait for the asynchronous operation if not necessary
    return false;
  }

  if (!isCreatedThisTime && profileDoesNotExists) {

    console.log('No profile...', process.env.CREATE_PROFILE);

    // Return the promise
    return fetchLatestProfileByType(TEST_USER_UUID, profileType)
      .then((profile) => {
        if (profile && profile.updated_at) {

          console.log('Found profile, skip creation')

          // process.env[varname] = JSON.stringify(profile);
          process.env[varname] = 'FOUND';

          const {updated_at} = profile;
          return !isTimestampLessThanAnHourAgo(updated_at);
        }

        return true;


      })
      .catch((error) => {
        console.error('Error fetching profile:', error);
        // Handle the error or log it
        return false; // Assuming profile fetch failure means not created
      });
  }

  // No need to wait for the asynchronous operation if not necessary
  return false;
};

/**
 * Try to skip some tests based on some logic.
 *
 * Removed logic to see if things work.
 *
 * DOES NOT WORK AS INTENDED. VERY CONFUSING ASYNC / AWAIT WITH PLAYGROUND
 *
 * @param description
 * @param testFunction
 * @param profileVariable
 * @param profileType
 */
const runOrSkipProfileCreation = (description: string, testFunction: {
    (): Promise<void>;
    (args: PlaywrightTestArgs & PlaywrightTestOptions & PlaywrightWorkerArgs & PlaywrightWorkerOptions, testInfo: TestInfo): void | Promise<void>;
}, profileVariable: string, profileType: string) => {

  // const createProfile = process.env.CREATE_PROFILE ?? 'true';
  //
  // console.log('Create?', createProfile);

  // if (createProfile === 'true') {
  //     // No need to wait for the asynchronous operation if not necessary
  //     return test(description, testFunction);
  // } else {
  //     return test.skip(description, () => {})
  // }


  return test(description, testFunction);
  // return test.skip(description, () => {})


  // console.log('tttttt',isProfileCreated(profileVariable, profileType));
  //
  // @ts-ignore
  // return !isProfileCreated(profileVariable, profileType) ? test(description, testFunction) : test.skip(description, () => {
  // });

  // return isProfileCreated(profileVariable, profileType).then((isCreated: boolean) => {
  //   return isCreated ? test(description, testFunction) : test.skip(description, () => {
  //   });
  // })

};


const checkContactInfoPrivatePerson = async (page: Page, profileData: FormData) => {
  await expect(page.getByRole('heading', {name: 'Omat tiedot'})).toBeVisible()

  // Perustiedot
  await expect(page.getByRole('heading', {name: 'Perustiedot'})).toBeVisible()
  await expect(page.getByText('Etunimi')).toBeVisible()
  await expect(page.getByText('Sukunimi')).toBeVisible()
  await expect(page.getByText('Henkilötunnus')).toBeVisible()
  await expect(page.getByRole('link', {name: 'Siirry Helsinki-profiiliin päivittääksesi sähköpostiosoitetta'})).toBeVisible()

  // Omat yhteystiedot
  await expect(page.getByRole('heading', {name: 'Omat yhteystiedot'})).toBeVisible()
  await expect(page.locator("#addresses").getByText('Osoite')).toBeVisible()
  await expect(page.locator("#phone-number").getByText('Puhelinnumero')).toBeVisible()
  await expect(page.locator("#officials-3").getByText('Tilinumerot')).toBeVisible()
  await expect(page.getByRole('link', {name: 'Muokkaa omia tietoja'})).toBeVisible()


  // tässä me voitas verrata profiilisivun sisältöä tallennettuun dataan.


}


export {
  checkContactInfoPrivatePerson,
  runOrSkipProfileCreation,
  isProfileCreated
}

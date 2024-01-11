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
 * @param profileVariable
 *  Name that is used to save details of this profile run.
 * @param profileType
 *  Profile type, registered_community, private_person etc..
 */
const isProfileCreated = async (profileVariable: string, profileType: string) => {

  const isCreatedThisTime = process.env[profileVariable] === 'TRUE';
  const varname = 'fetchedProfile_' + profileType;
  const profileDoesNotExists = process.env[varname] === undefined;

  console.log('Profile...');

  if (process.env.CREATE_PROFILE === 'true') {
    console.log('... creation is forced through variable');
    // No need to wait for the asynchronous operation if not necessary
    return false;
  }

  if (isCreatedThisTime && !profileDoesNotExists) {
    console.log('... is created this run..?');
    return false;
  }

  // Return the promise
  return fetchLatestProfileByType(TEST_USER_UUID, profileType)
    .then((profile) => {
      if (profile && profile.updated_at) {

        // process.env[varname] = JSON.stringify(profile);
        process.env[varname] = 'FOUND';

        const {updated_at} = profile;
        const isLessThanHourAgo = isTimestampLessThanAnHourAgo(updated_at);

        if (isLessThanHourAgo) {
          console.log('...created less than an hour ago.');
        } else {
          console.log('...created more than hour ago and should be re-tested');
        }

        // return isLessThanHourAgo;
        return false;
      }
      return false;
    })
    .catch((error) => {
      console.error('Error fetching profile:', error);
      // Handle the error or log it
      return false; // Assuming profile fetch failure means not created
    });
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
  isProfileCreated
}

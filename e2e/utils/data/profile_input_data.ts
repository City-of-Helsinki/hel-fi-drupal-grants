/**
 * The ProfileInputData interface.
 *
 * This interface provides a structure for
 * profile data. Its intention is to set up
 * profile data that can be used both when
 * creating a profile, and when verifying
 * a profiles data on an applications
 * "View" page.
 */
interface ProfileInputData  {
  iban: string;
  address: string;
  zipCode: string;
  city: string;
  communityOfficial: string;
}

/**
 * Setup profile data. This data
 * is used when creating profiles,
 * and when verifying that the profile
 * data exists on an applications "View" page.
 */
const PROFILE_INPUT_DATA: ProfileInputData = {
  iban: process.env.TEST_USER_IBAN ?? '',
  address: process.env.TEST_USER_ADDRESS ?? '',
  zipCode: process.env.TEST_USER_ZIPCODE ?? '',
  city: process.env.TEST_USER_CITY ?? '',
  communityOfficial: process.env.TEST_USER_OFFICIAL ?? '',
}

export {
  PROFILE_INPUT_DATA,
}

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
  iban: 'FI1165467882414711',
  address: 'Ahonenväylä 95',
  zipCode: '91435',
  city: 'Kuopio',
  communityOfficial: 'Marko Niemi',
}

export {
  PROFILE_INPUT_DATA,
}

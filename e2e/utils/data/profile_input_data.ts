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
  iban?: string;
  iban2?: string;
  address?: string;
  zipCode?: string;
  city?: string;
  communityOfficial?: string;
  role?: string;
  email?: string;
  phone?: string;
}

/**
 * Setup profile data. This data
 * is used when creating profiles,
 * and when verifying that the profile
 * data exists on an applications "View" page.
 */
const PROFILE_INPUT_DATA: ProfileInputData = {
  iban: 'FI3147372044000048',
  iban2: 'FI5777266988169614',
  address: 'Ahonenväylä 95',
  zipCode: '91435',
  city: 'Kuopio',
  communityOfficial: 'Marko Niemi',
  role: 'Vastuuhenkilö',
  email: 'marko.niemi78@gmail.com',
  phone: '0401234567'
}

export {
  ProfileInputData,
  PROFILE_INPUT_DATA,
}

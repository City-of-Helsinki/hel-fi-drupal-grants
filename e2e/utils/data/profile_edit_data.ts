import {PROFILE_INPUT_DATA} from './profile_input_data';

/**
 * The ProfileEditField interface.
 *
 * This interface describes a profile field that is
 * edited and then reverted back to its original value.
 */
interface ProfileEditField {
  selector: string;
  value: string;
}

const EDITED_STREET = 'Muokattu Testikatu 5';
const EDITED_POSTCODE = '00530';
const EDITED_CITY = 'Testikaupunki';
const EDITED_PHONE = '0509876543';
const EDITED_OFFICIAL_NAME = 'Muokattu Testihenkilo';
const EDITED_OFFICIAL_EMAIL = 'muokattu.testihenkilo@test.hel.ninja';

// Profiles are created with a random abbreviated name, so the reset uses a fixed one.
const RESET_COMPANY_NAME_SHORT = 'Testiyhteiso';

/**
 * The fields that are edited on each profile type.
 *
 * The values are unique on purpose so that they can be
 * told apart from the original profile data.
 */
const PROFILE_EDIT_DATA: Record<string, ProfileEditField[]> = {
  private_person: [
    {
      selector: 'edit-addresswrapper-0-address-street',
      value: EDITED_STREET,
    },
    {
      selector: 'edit-addresswrapper-0-address-postcode',
      value: EDITED_POSTCODE,
    },
    {
      selector: 'edit-addresswrapper-0-address-city',
      value: EDITED_CITY,
    },
    {
      selector: 'edit-phonewrapper-phone-number',
      value: EDITED_PHONE,
    },
  ],
  unregistered_community: [
    {
      selector: 'edit-addresswrapper-0-address-street',
      value: EDITED_STREET,
    },
    {
      selector: 'edit-addresswrapper-0-address-postcode',
      value: EDITED_POSTCODE,
    },
    {
      selector: 'edit-addresswrapper-0-address-city',
      value: EDITED_CITY,
    },
    {
      selector: 'edit-officialwrapper-0-official-name',
      value: EDITED_OFFICIAL_NAME,
    },
    {
      selector: 'edit-officialwrapper-0-official-email',
      value: EDITED_OFFICIAL_EMAIL,
    },
    {
      selector: 'edit-officialwrapper-0-official-phone',
      value: EDITED_PHONE,
    },
  ],
  registered_community: [
    {
      selector: 'edit-basicdetailswrapper-companynameshort',
      value: 'Muokattu lyhenne',
    },
    {
      selector: 'edit-addresswrapper-0-address-street',
      value: EDITED_STREET,
    },
    {
      selector: 'edit-addresswrapper-0-address-postcode',
      value: EDITED_POSTCODE,
    },
    {
      selector: 'edit-addresswrapper-0-address-city',
      value: EDITED_CITY,
    },
    {
      selector: 'edit-officialwrapper-0-official-name',
      value: EDITED_OFFICIAL_NAME,
    },
    {
      selector: 'edit-officialwrapper-0-official-phone',
      value: EDITED_PHONE,
    },
  ],
};

/**
 * The values a profile is restored to when RESET_PROFILE is set.
 *
 * The values match the data profiles are created with, so
 * restoring them also fixes the other profile tests.
 */
const PROFILE_RESET_DATA: Record<string, ProfileEditField[]> = {
  private_person: [
    {
      selector: 'edit-addresswrapper-0-address-street',
      value: PROFILE_INPUT_DATA.address,
    },
    {
      selector: 'edit-addresswrapper-0-address-postcode',
      value: PROFILE_INPUT_DATA.zipCode,
    },
    {
      selector: 'edit-addresswrapper-0-address-city',
      value: PROFILE_INPUT_DATA.city,
    },
    {
      selector: 'edit-phonewrapper-phone-number',
      value: PROFILE_INPUT_DATA.phone,
    },
  ],
  unregistered_community: [
    {
      selector: 'edit-addresswrapper-0-address-street',
      value: PROFILE_INPUT_DATA.address,
    },
    {
      selector: 'edit-addresswrapper-0-address-postcode',
      value: PROFILE_INPUT_DATA.zipCode,
    },
    {
      selector: 'edit-addresswrapper-0-address-city',
      value: PROFILE_INPUT_DATA.city,
    },
    {
      selector: 'edit-officialwrapper-0-official-name',
      value: PROFILE_INPUT_DATA.communityOfficial,
    },
    {
      selector: 'edit-officialwrapper-0-official-email',
      value: PROFILE_INPUT_DATA.email,
    },
    {
      selector: 'edit-officialwrapper-0-official-phone',
      value: PROFILE_INPUT_DATA.phone,
    },
  ],
  registered_community: [
    {
      selector: 'edit-basicdetailswrapper-companynameshort',
      value: RESET_COMPANY_NAME_SHORT,
    },
    {
      selector: 'edit-addresswrapper-0-address-street',
      value: PROFILE_INPUT_DATA.address,
    },
    {
      selector: 'edit-addresswrapper-0-address-postcode',
      value: PROFILE_INPUT_DATA.zipCode,
    },
    {
      selector: 'edit-addresswrapper-0-address-city',
      value: PROFILE_INPUT_DATA.city,
    },
    {
      selector: 'edit-officialwrapper-0-official-name',
      value: PROFILE_INPUT_DATA.communityOfficial,
    },
    {
      selector: 'edit-officialwrapper-0-official-phone',
      value: PROFILE_INPUT_DATA.phone,
    },
  ],
};

export {
  ProfileEditField,
  PROFILE_EDIT_DATA,
  PROFILE_RESET_DATA,
}

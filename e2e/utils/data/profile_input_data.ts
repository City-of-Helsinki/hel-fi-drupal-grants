import {fakerFI as faker} from "@faker-js/faker"
import path from "path";
import {logger} from "../logger";

/**
 * The ProfileInputData interface.
 *
 * This interface is used when generating profile
 * input data at the start of the test. This is
 * done so that we can use tha same data when creating
 * the profiles and when validating that the correct
 * profile data ends up on the "View" page of an
 * application.
 */
interface ProfileInputData {
  streetAddress?: string;
  zipCode?: string;
  city?: string;
  bankAccount?: string;
  bankAccountFile?: string;
  communityOfficial?: string;
}

/**
 * The generateProfileInputData function.
 *
 * This function generates profile data based on the
 * ProfileInputData interface, and stores it in the
 * environment under the key PROFILE_INPUT_DATA.
 */
const generateProfileInputData = (): void => {
  try {
    const profileData: ProfileInputData = {
      streetAddress: faker.location.streetAddress(),
      zipCode: faker.location.zipCode(),
      city: faker.location.city(),
      bankAccount: 'FI1165467882414711',
      bankAccountFile: path.join(__dirname, 'test.pdf'),
      communityOfficial: faker.person.fullName(),
    };
    process.env.PROFILE_INPUT_DATA = JSON.stringify(profileData);
    logger("Profile data generated for registered communities.", process.env.PROFILE_DATA);
  } catch (error) {
    logger("Unable to generate profile data.");
  }
}

/**
 * The getProfileInputDataFromEnv function.
 *
 * This function returns profile data from
 * the environment. The data is located under
 * the key PROFILE_INPUT_DATA.
 * 
 *
 * @return {ProfileInputData | undefined}
 *   Returns an object of the type ProfileInputData or undefined.
 */
const getProfileInputDataFromEnv = (): ProfileInputData | undefined => {
  const envData = process.env.PROFILE_INPUT_DATA;
  if (envData) {
    return JSON.parse(envData) as ProfileInputData;
  }
  logger('Unable to locate profile data.');
  return undefined;
}

export {
  generateProfileInputData,
  getProfileInputDataFromEnv
}

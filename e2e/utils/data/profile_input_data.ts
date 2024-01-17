import {fakerFI as faker} from "@faker-js/faker"
import {logger} from "../logger";
import path from "path";

interface ProfileInputData {
  streetAddress?: string;
  zipCode?: string;
  city?: string;
  bankAccount?: string;
  bankAccountFile?: string;
  communityOfficial?: string;
}

const generateProfileDataRegistered = (): void => {
  try {
    const profileData: ProfileInputData = {
      streetAddress: faker.location.streetAddress(),
      zipCode: faker.location.zipCode(),
      city: faker.location.city(),
      bankAccount: 'FI1165467882414711',
      bankAccountFile: path.join(__dirname, 'test.pdf'),
      communityOfficial: faker.person.fullName(),
    };
    process.env.PROFILE_DATA = JSON.stringify(profileData);
    logger("Profile data generated for registered communities.", process.env.PROFILE_DATA);
  } catch (error) {
    logger("Unable to generate profile data", error);
  }
}

const getProfileDataFromEnv = (): ProfileInputData | undefined => {
  const envData = process.env.PROFILE_DATA;
  if (envData) {
    return JSON.parse(envData) as ProfileInputData;
  }
  logger('Unable to locate profile data.');
  return undefined;
}

export {
  getProfileDataFromEnv,
  generateProfileDataRegistered,
}

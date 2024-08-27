import {fakerFI as faker} from "@faker-js/faker";

/**
 * Options for generating a fake email address.
 */
interface EmailOptions {
  firstName?: string | undefined;
  lastName?: string | undefined;
  allowSpecialCharacters?: boolean | undefined;
}

/**
 * Get a fake email address.
 *
 * @param options
 */
const getFakeEmailAddress = (options?: EmailOptions) =>
  faker.internet.exampleEmail(options).toLowerCase();

export { getFakeEmailAddress };

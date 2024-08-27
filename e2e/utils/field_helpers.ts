import {fakerFI as faker} from "@faker-js/faker";

const getFakeEmailAddress = () => faker.internet.exampleEmail().toLowerCase();

export { getFakeEmailAddress };

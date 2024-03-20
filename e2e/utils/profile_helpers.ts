import {expect, Page,} from '@playwright/test';
import {logger} from "./logger";
import {FormData} from "./data/test_data"
import {fetchLatestProfileByType} from "./document_helpers";

/**
 * The function isTimestampLessThanAnHourAgo.
 *
 * @param timestamp
 */
function isTimestampLessThanAnHourAgo(timestamp: string) {
  const oneHourInMilliseconds = 60 * 60 * 1000; // 1 hour in milliseconds.
  const currentTimestamp = new Date().getTime();
  const targetTimestamp = new Date(timestamp).getTime();
  return currentTimestamp - targetTimestamp < oneHourInMilliseconds;
}

/**
 * Try to check if profile is just created so that
 * we can skip these, when running multiple test runs.
 *
 * @param profileType
 *  Profile type, registered_community, private_person etc..
 */
const isProfileCreated = async (profileType: string) => {
  logger('Profile...');

  if (process.env.CREATE_PROFILE === 'TRUE') {
    logger('... creation is forced through variable.');
    return false;
  }

  return fetchLatestProfileByType(process.env.TEST_USER_UUID ?? '', profileType)
    .then((profile) => {
      if (profile && profile.updated_at) {
        const {updated_at} = profile;
        const isLessThanHourAgo = isTimestampLessThanAnHourAgo(updated_at);

        if (isLessThanHourAgo) {
          logger('...created less than an hour ago.');
        } else {
          logger('...created more than hour ago and should be re-tested.');
        }
        return isLessThanHourAgo;
      }
      return false;
    })
    .catch((error) => {
      logger('Error fetching profile:', error);
      return false;
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

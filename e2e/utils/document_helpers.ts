import {logger} from "./logger";
import {getAppEnvForATV} from './env_helpers';
import {Page} from "@playwright/test";

interface ATVMetadata {
  appenv: string;
  profile_id: string;
  profile_type: string;
  notification_shown: string;
}

interface ATVDocument {
  content: any;
  id: string;
  type: string;
  service: string;
  updated_at: string;
  transaction_id: string;
  metadata: ATVMetadata
}

interface PaginatedDocumentList {
  count: number;
  next: string | null;
  previous: string | null;
  results: ATVDocument[]
}

const baseURL = process.env.TEST_BASEURL ?? "https://hel-fi-drupal-grant-applications.docker.so";

/**
 * The fetchLatestProfileByType function.
 *
 * This function fetches the latest profile from ATV
 * when given a users UUID and a profile type.
 *
 * @param userUUID
 *   The users UUID.
 * @param profileType
 *   The profile type.
 * @param page
 *   The page.
 */
const fetchLatestProfileByType = async (userUUID: string, profileType: string, page: Page) => {
  const api = page.context().request;
  const endpoint = `/test/document/${userUUID}/profile/${profileType}`;

  const response = await api.post(endpoint);
  if (response.status() > 299) {
    logger(`Backend returned non-200 response. Something went wrong.`);
    return false;
  }

  return response.json();
}

/**
 * The deleteGrantsProfiles function.
 *
 * This function deletes grants profiles from ATV
 * with the passed in user UUID and profile type.
 *
 * @param testUserUUID
 *   The user UUID for the profile we want to delete.
 * @param profileType
 *   The profile type we are deleting.
 * @param page
 *   The page.
 */
const deleteGrantsProfiles = async (testUserUUID: string, profileType: string, page: Page) => {
  const api = page.context().request;
  const endpoint = `/test/document/${testUserUUID}/profile/${profileType}/remove`;

  const response = await api.post(endpoint);
  if (response.status() > 299) {
    logger(`Backend returned non-200 response. Something went wrong.`);
    return;
  }

  logger(`Deleted grant profiles from ATV`);
}

export {
  getAppEnvForATV,
  deleteGrantsProfiles,
  fetchLatestProfileByType
}

import {logger} from "./logger";
import {getKeyValue, getAppEnvForATV} from './env_helpers';

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

// Setup ATV keys.
const ATV_API_KEY = getKeyValue('ATV_API_KEY');
const ATV_BASE_URL = getKeyValue('TEST_ATV_URL');
const APP_ENV_FOR_ATV = getAppEnvForATV();
const BASE_HEADERS = {'X-API-KEY': ATV_API_KEY};

/**
 * The fetchLatestProfileByType function.
 *
 * This function fetches the latest profile from ATV
 * when given a users UUID and a profile type.
 * The helper function fetchDocumentList() is used
 * to do the actual fetching.
 *
 * @param userUUID
 *   The users UUID.
 * @param profileType
 *   The profile type.
 */
const fetchLatestProfileByType = (userUUID: string, profileType: string) => {
  const currentUrl = `${ATV_BASE_URL}/v1/documents/?lookfor=appenv:${APP_ENV_FOR_ATV},profile_type:${profileType}&user_id=${userUUID}&type=grants_profile&sort=updated_at`;
  // Use then to handle the asynchronous result.
  return fetchDocumentList(currentUrl).then((documentList) => {
    if (documentList) {
      // Because ATV does not differentiate between registered / unregistered
      // communities, we need to do the filtering here.
      const thisTypes = documentList.results.filter((item) => item.metadata.profile_type === profileType);
      return thisTypes[0];
    }
  });
}

/**
 * The function fetchDocumentList.
 *
 * This function uses fetch() to get a document list
 * from ATV. The variable BASE_HEADERS is set as
 * the headers for the call.
 *
 * @param url
 *   The URL we are making a request to.
 */
const fetchDocumentList = async (url: string): Promise<PaginatedDocumentList | null> => {
  try {
    const res = await fetch(url, {headers: BASE_HEADERS});
    if (!res.ok) {
      throw new Error(`HTTP error! Status: ${res.status}`);
    }
    return await res.json();
  } catch (error) {
    logger("Error fetching document list:", error);
    return null;
  }
};

/**
 * The deleteDocumentById function.
 *
 * This function deletes a documents that
 * matches the passed in ID from ATV.
 *
 * @param id
 *   The ID of the document we want to delete.
 */
const deleteDocumentById = async (id: string) => {
  try {
    const url = `${ATV_BASE_URL}/v1/documents/${id}`;
    const res = await fetch(url, {method: 'DELETE', headers: BASE_HEADERS});
    if (!res.ok) {
      throw new Error(`HTTP error! Status: ${res.status}`);
    }
    return true;
  } catch (error) {
    logger("Error deleting document:", error);
    return false;
  }
};

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
 */
const deleteGrantsProfiles = async (testUserUUID: string, profileType: string) => {
  let currentUrl: string | null = `${ATV_BASE_URL}/v1/documents/?lookfor=appenv:${APP_ENV_FOR_ATV},profile_type:${profileType}&user_id=${testUserUUID}&type=grants_profile&service_name=AvustushakemusIntegraatio&sort=updated_at`;
  let deletedDocumentsCount = 0;

  while (currentUrl != null) {
    const documentList: PaginatedDocumentList|null = await fetchDocumentList(currentUrl);

    if (!documentList) return;

    currentUrl = documentList.next;
    const documentIds = documentList.results
      .filter((item: { metadata: { profile_type: string; }; }) => item.metadata.profile_type === profileType)
      .map((r: { id: any; }) => r.id);

    const deletionPromises = documentIds.map(deleteDocumentById);
    const deletionResults = await Promise.all(deletionPromises);
    deletedDocumentsCount += deletionResults.filter(result => result).length;
  }
  logger(`Deleted ${deletedDocumentsCount} grant profiles from ATV.`);
}

export {
  ATV_BASE_URL,
  ATV_API_KEY,
  getAppEnvForATV,
  deleteGrantsProfiles,
  fetchLatestProfileByType
}

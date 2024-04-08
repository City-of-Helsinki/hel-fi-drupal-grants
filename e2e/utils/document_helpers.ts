import {logger} from "./logger";
import {getKeyValue} from './env_helpers';

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

interface PaginatedDocumentlist {
  count: number;
  next: string | null;
  previous: string | null;
  results: ATVDocument[]
}

const APP_ENV = getKeyValue('APP_ENV');
const ATV_API_KEY = getKeyValue('ATV_API_KEY');
const ATV_BASE_URL = getKeyValue('TEST_ATV_URL');

// Similarly as in ApplicationHandler.php
const getAppEnvForATV = () => {
  switch (APP_ENV) {
    case "development":
      return "DEV"
    case "testing":
      return "TEST"
    case "staging":
      return "STAGE"
    default:
      return APP_ENV.toUpperCase()
  }
}

const BASE_HEADERS = {'X-API-KEY': ATV_API_KEY};
const APP_ENV_FOR_ATV = getAppEnvForATV();


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

const fetchDocumentList = async (url: string): Promise<PaginatedDocumentlist | null> => {
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
 * Delete all grants profiles from atv for given user UUID.
 *
 * @param testUserUUID
 * @param profileType
 */
const deleteGrantsProfiles = async (testUserUUID: string, profileType: string) => {
  let currentUrl: string | null = `${ATV_BASE_URL}/v1/documents/?lookfor=appenv:${APP_ENV_FOR_ATV},profile_type:${profileType}&user_id=${testUserUUID}&type=grants_profile&service_name=AvustushakemusIntegraatio&sort=updated_at`;
  let deletedDocumentsCount = 0;

  while (currentUrl != null) {
    const documentList: PaginatedDocumentlist|null = await fetchDocumentList(currentUrl);

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

/**
 * Delete application documents from ATV
 */
const deleteGrantApplications = async (status: string, testUserUUID: string) => {

}

export {
  ATVDocument,
  PaginatedDocumentlist,
  APP_ENV,
  ATV_API_KEY,
  ATV_BASE_URL,
  getAppEnvForATV,
  fetchDocumentList,
  deleteDocumentById,
  BASE_HEADERS,
  deleteGrantsProfiles,
  fetchLatestProfileByType
}

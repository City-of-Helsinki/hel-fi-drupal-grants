
import {
  getKeyValue,
} from '../utils/helpers';


type ATVMetadata = {
  appenv: string;
  profile_id: string;
  profile_type: string;
  notification_shown: string;
}

type ATVDocument = {
  id: string;
  type: string;
  service: string;
  updated_at: string;
  transaction_id: string;
  metadata: ATVMetadata
}

type PaginatedDocumentlist = {
  count: number;
  next: string | null;
  previous: string | null;
  results: ATVDocument[]
}

const APP_ENV = getKeyValue('APP_ENV');
const ATV_API_KEY = getKeyValue('ATV_API_KEY');
const ATV_BASE_URL = getKeyValue('TEST_ATV_URL');

// Similarily as in ApplicationHandler.php
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

  // Use then to handle the asynchronous result
  return fetchDocumentList(currentUrl).then((documentList) => {
    if (documentList) {
      // because ATV does not differentiate between registered / unregistered
      // communities, we need to do the filtering here
      const thisTypes = documentList.results.filter((item) => item.metadata.profile_type === profileType);
      return thisTypes[0];
    }
  });
}

const fetchDocumentList = async (url: string) => {

  try {
    // console.log('FETCH', url);

    const res = await fetch(url, {headers: BASE_HEADERS});

    if (!res.ok) {
      throw new Error(`HTTP error! Status: ${res.status}`);
    }

    const json: PaginatedDocumentlist = await res.json();
    return json;
  } catch (error) {
    console.error("Error fetching document list:", error);
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
    console.error("Error deleting document:", error);
    return false;
  }
};

/**
 * Delete all grants profiles from atv for given user UUID.
 *
 * @param testUserUiid
 * @param profileType
 */
const deleteGrantsProfiles = async (testUserUiid: string, profileType: string) => {

  const initialUrl = `${ATV_BASE_URL}/v1/documents/?lookfor=appenv:${APP_ENV_FOR_ATV},profile_type:${profileType}&user_id=${testUserUiid}&type=grants_profile&service_name=AvustushakemusIntegraatio&sort=updated_at`;

  let currentUrl: string | null = initialUrl;

  let deletedDocumentsCount = 0;

  while (currentUrl != null) {
    const documentList = await fetchDocumentList(currentUrl);

    if (!documentList) return;

    currentUrl = documentList.next;

    const documentIds = documentList.results
      .filter((item) => item.metadata.profile_type === profileType)
      .map(r => r.id);

    const deletionPromises = documentIds.map(deleteDocumentById);
    const deletionResults = await Promise.all(deletionPromises);

    deletedDocumentsCount += deletionResults.filter(result => result).length;
  }

  return deletedDocumentsCount;
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

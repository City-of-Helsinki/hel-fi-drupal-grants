import {logger} from "./logger";
import path from "path";
import fs from "fs";

/**
 * The checkEnvVariables function.
 *
 * This function makes sure that certain variables
 * are set in the .env file. These variables are required
 * for the tests to work.
 */
const checkEnvVariables = () => {

  // The required variables.
  const requiredEnv = [
    'ATV_BASE_URL',
    'TEST_ATV_URL',
    'ATV_API_KEY',
    'APP_ENV',
    'TEST_USER_SSN',
    'TEST_USER_UUID',
  ];

  requiredEnv.forEach(variable => {
    if (!process.env[variable]) {
      throw new Error(`Environment variable ${variable} is not set.`);
    }
  });
};


/**
 * The saveObjectToEnv function.
 *
 * This function saves an object to the environment under
 * the provided variableName key. If something is already
 * saved under said key, then the two objects are merged.
 *
 * @param variableName
 *   The key we are storing data under.
 * @param data
 *   The data we are storing.
 */
function saveObjectToEnv(variableName: string, data: Object) {
  let existingObject = {};
  let existingEncoded = {};
  let existingBaseData = process.env.storedData;

  if (existingBaseData) {
    try {
      existingEncoded = JSON.parse(existingBaseData);

      if (typeof existingEncoded === 'object' && existingEncoded !== null) {
        // @ts-ignore
        existingObject = existingEncoded[variableName] || {};
      } else {
        logger('Existing data is not an object.');
        return;
      }
    } catch (error) {
      logger('Error parsing existing data:', error);
      return;
    }
  }

  if (typeof data === 'object') {
    const merged = {
      ...existingObject,
      ...data,
    };

    if (typeof existingEncoded === 'object') {
      // @ts-ignore
      existingEncoded[variableName] = merged;
    }
    process.env.storedData = JSON.stringify(existingEncoded);
  } else {
    logger('Data must be an object.');
  }
}

/**
 * Get stored data
 *
 * @param profileType
 * @param formId
 * @param full
 */
function getObjectFromEnv(profileType: string, formId: string, full: boolean = false) {
  const storeName = `${profileType}_${formId}`;

  const existingBaseData = process.env.storedData;

  if (existingBaseData) {
    try {
      const existingEncoded = JSON.parse(existingBaseData);
      if (existingEncoded) {
        if (full) {
          return existingEncoded;
        }
        return existingEncoded[storeName];
      }

    } catch (error) {
      logger('Error parsing existing data:', error);
      return;
    }
  }
}

const getKeyValue = (key: string) => {
  const envValue = process.env[key];
  if (envValue) {
    return envValue;
  }

  const pathToLocalSettings = path.join(__dirname, '../../public/sites/default/local.settings.php');
  try {
    const localSettingsContents = fs.readFileSync(pathToLocalSettings, 'utf8');

    const regex = new RegExp(`putenv\\('${key}=(.*?)'\\)`);
    const matches = localSettingsContents.match(regex);
    if (matches && matches.length > 1) {
      return matches[1];
    } else {
      logger(`Could not parse ${key} from configuration file.`);
    }
  } catch (error) {
    logger(`Error reading ${pathToLocalSettings}: ${error}`);
  }

  return '';
};

export {
  checkEnvVariables,
  saveObjectToEnv,
  getObjectFromEnv,
  getKeyValue,
}

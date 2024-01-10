import path from "path";
import fs from "fs";

/**
 * The readEnvFile function.
 *
 * This function reads the content of the '.test_env' file
 * and returns an array of its lines.
 *
 * @returns string[]
 *   Array of lines from the '.test_env' file.
 *
 * TODO: This function should be moved to another file, since it
 *       is not strictly related to debugging.
 */
const readEnvFile = (): string[] => {
  try {
    const envFilePath = path.resolve(__dirname, '../.test_env');
    const envFile = fs.readFileSync(envFilePath, 'utf-8');
    return envFile.split('\n');
  } catch (error) {
    console.error('[Error reading .test_env file]');
    return [];
  }
};

/**
 * The setDebugMode function.
 *
 * This function enabled debugging (sets the value of
 * process.env['APP_DEBUG'] to 'TRUE') if the line 'APP_DEBUG=TRUE'
 * is found in the .test_env file.
 */
const setDebugMode = (): void => {
  if (process.env.APP_DEBUG === 'TRUE') return;

  const found = readEnvFile().some(line => {
    if (line === 'APP_DEBUG=TRUE') {
      process.env['APP_DEBUG'] = 'TRUE';
      console.log('[Debugging mode enabled]');
      return true;
    }
    return false;
  });

  if (!found) {
    console.log('[Debugging mode disabled]');
  }
};

/**
 * The isDebuggingEnabled function.
 *
 * This function checks the value of the environment
 * variable 'APP_DEBUG', and returns TRUE if the value is
 * set to the string 'TRUE'.
 *
 * @returns boolean
 *   TRUE if APP_DEBUG is set to 'TRUE'.
 */
const isDebuggingEnabled = (): boolean => {
  return process.env.APP_DEBUG === 'TRUE';
};

export {
  setDebugMode,
  isDebuggingEnabled
}

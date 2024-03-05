import { readEnvFile } from "./env_helpers";

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
    line = line.trim();
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

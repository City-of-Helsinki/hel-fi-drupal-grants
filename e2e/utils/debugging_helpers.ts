/**
 * The setDebugMode function.
 *
 * This function prints a message indicating
 * whether APP_DEBUG has been set to TRUE
 * in the .env file.
 */
const setDebugMode = (): void => {
  if (process.env.APP_DEBUG === 'TRUE') {
    console.log('[Debugging mode enabled]');
  } else {
    console.log('[Debugging mode disabled]');
  }
};

/**
 * The isDebuggingEnabled function.
 *
 * This function checks the value of the environment
 * variable 'APP_DEBUG', and returns TRUE if the value is
 * set to TRUE.
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

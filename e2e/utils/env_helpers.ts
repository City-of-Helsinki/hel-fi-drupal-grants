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

export {
  checkEnvVariables,
}

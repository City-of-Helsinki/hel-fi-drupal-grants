import {logger} from "./logger";

/**
 * The setDisabledFormVariants function.
 *
 * This function sets the disabled from variants to the env
 * variable DISABLED_FORM_VARIANTS. Disabled form variants are
 * read from the .env file, and should be located under the key
 * DISABLED_FORM_VARIANTS.
 *
 * Ex: DISABLED_FORM_VARIANTS="success,draft,missing_values"
 */
const setDisabledFormVariants = (): void => {
  if (!process.env.DISABLED_FORM_VARIANTS) {
    process.env.DISABLED_FORM_VARIANTS = 'FALSE';
    logger('DISABLED_FORM_VARIANTS has not been set in .env. Running all form variant tests.');
    return;
  }
  const variants = process.env.DISABLED_FORM_VARIANTS.split(',').map(variant => variant.trim());
  process.env.DISABLED_FORM_VARIANTS = JSON.stringify(variants);
  logger(`Disabled form variants: ${variants}`);
};

/**
 * The setEnabledFormVariants function.
 *
 * This function sets the disabled from variants to the env
 * variable DISABLED_FORM_VARIANTS. Disabled form variants are
 * read from the .env file, and should be located under the key
 * DISABLED_FORM_VARIANTS.
 *
 * Ex: DISABLED_FORM_VARIANTS="success,draft,missing_values"
 */
const setEnabledFormVariants = (): void => {
  if (!process.env.ENABLED_FORM_VARIANTS) {
    process.env.ENABLED_FORM_VARIANTS = 'FALSE';
    logger('ENABLED_FORM_VARIANTS has not been set in .env. Running tests that are not disabled');
    return;
  }
  const variants = process.env.ENABLED_FORM_VARIANTS.split(',').map(variant => variant.trim());
  process.env.ENABLED_FORM_VARIANTS = JSON.stringify(variants);
  logger(`Running tests for variants: ${variants}`);
};

/**
 * The getDisabledFormVariants function.
 *
 * This function returns the content of the
 * DISABLED_FORM_VARIANTS env variable as an array.
 * If the variable is not set, or the value of the variable
 * is set to FALSE, then an empty array is returned.
 *
 * @return string[]
 *   An array containing disabled form variants if
 *   DISABLED_FORM_VARIANTS is set.
 */
const getDisabledFormVariants = (): string[] => {
  if (!process.env.DISABLED_FORM_VARIANTS || process.env.DISABLED_FORM_VARIANTS === 'FALSE') return [];
  return JSON.parse(process.env.DISABLED_FORM_VARIANTS);
};

/**
 * The getEnabledFormVariants function.
 *
 * This function returns the content of the
 * ENABLED_FORM_VARIANTS env variable as an array.
 * If the variable is not set, or the value of the variable
 * is set to FALSE, then an empty array is returned.
 *
 * @return string[]
 *   An array containing disabled form variants if
 *   ENABLED_FORM_VARIANTS is set.
 */
const getEnabledFormVariants = (): string[] => {
  if (!process.env.ENABLED_FORM_VARIANTS || process.env.ENABLED_FORM_VARIANTS === 'FALSE') return [];
  return JSON.parse(process.env.ENABLED_FORM_VARIANTS);
};

/**
 * The getFormVariantsForTests function.
 *
 * The function filters out disabled form variants from the provided application data.
 * Also, it's possible to add variable only for form variants that are enabled
 * This allows for dynamic exclusion / inclusion of specific tests based on
 * configurations set in the .env file,
 * avoiding the need to manually comment out tests in the application data files.
 *
 * The function does the following:
 *
 * 1. Gets the disabled & enabled form variants from the .env.
 * 2. Iterates over each form variant within an application.
 * 3. Checks if the current variant is among the disabled or enabled variants.
 * 4. Deletes the form variant from the application if it is disabled.
 *
 * ENABLED_FORM_VARIANTS form variants overrides DISABLED_FORM_VARIANTS, meaning
 * that if some variant is explicitly set for inclusion, others WILL NOT be run
 *
 * @param applications
 *   An object containing application data.
 *
 * @return applications
 *   An object containing application data for the tests that are set to be run.
 */
const getFormVariantsForTests = (applications: any): any => {
  const disabledFormVariants = getDisabledFormVariants();
  const enabledFormVariants = getEnabledFormVariants();

  // Check if we have explicitly enableb variants, and if so run only those tests
  if (enabledFormVariants.length > 0) {
    Object.keys(applications).forEach(applicationId => {
      Object.keys(applications[applicationId]).forEach(formVariant => {
        if (!enabledFormVariants.includes(formVariant)) {
          delete applications[applicationId][formVariant];
        }
      });
    });
  }
  // If no variants are enabled, then filter out disabled ones.
  else if (disabledFormVariants.length > 0) {
    Object.keys(applications).forEach(applicationId => {
      Object.keys(applications[applicationId]).forEach(formVariant => {
        if (disabledFormVariants.includes(formVariant)) {
          delete applications[applicationId][formVariant];
        }
      });
    });

  }
  // Return either enabled or all tests
  return applications;
}

export {
  setDisabledFormVariants,
  getFormVariantsForTests,
  setEnabledFormVariants
}

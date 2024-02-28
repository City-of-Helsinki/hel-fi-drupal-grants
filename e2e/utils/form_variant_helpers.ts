import {readEnvFile} from "./env_helpers";

/**
 * The setDisabledFormVariants function.
 *
 * This function sets the disabled from variants to the env
 * variable DISABLED_FORM_VARIANTS. Disabled form variants are
 * read from the .test_env file, and should be located under the key
 * DISABLED_FORM_VARIANTS.
 *
 * Ex: DISABLED_FORM_VARIANTS="success","draft","missing_values"
 */
const setDisabledFormVariants = (): void => {
  if (process.env.DISABLED_FORM_VARIANTS) return;

  const envLines = readEnvFile();
  const variantsLine = envLines.find(line => line.startsWith('DISABLED_FORM_VARIANTS='));

  if (!variantsLine) {
    process.env.DISABLED_FORM_VARIANTS = 'FALSE';
    console.log('[DISABLED_FORM_VARIANTS has not been set in .test_env]');
    return;
  }

  const variants = variantsLine
    .substring(variantsLine.indexOf('=') + 1)
    .split(',')
    .map(variant => variant.trim().replace(/"/g, ''));

  process.env.DISABLED_FORM_VARIANTS = JSON.stringify(variants);
  console.log(`[Disabled form variants: ${variants}]`)
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
 * The filterOutDisabledFormVariants function.
 *
 * This function removes disabled form variants from the
 * application data. This functionality exists so that one
 * does not need to comment out the wanted tests from an
 * application_data_x.ts file, but instead define the disabled
 * form variants to a DISABLED_FORM_VARIANTS key in the .test_env file.
 *
 * The function does the following:
 *
 * 1. Gets the disabled form variants from the env.
 * 2. Iterates over each form variant within an application.
 * 3. Checks if the current variant is among the disabled variants.
 * 4. Deletes the form variant from the application if it is disabled.
 *
 * @param applications
 *   An object containing application data.
 *
 * @return applications
 *   An object containing application data where the disabled
 *   form variants have been removed.
 */
const filterOutDisabledFormVariants = (applications: any): any => {
  const disabledFormVariants = getDisabledFormVariants();
  if (!disabledFormVariants.length) return applications;

  Object.keys(applications).forEach(applicationId => {
    Object.keys(applications[applicationId]).forEach(formVariant => {
      if (disabledFormVariants.includes(formVariant)) {
        delete applications[applicationId][formVariant];
      }
    });
  });
  return applications;
}

export {
  setDisabledFormVariants,
  filterOutDisabledFormVariants,
}

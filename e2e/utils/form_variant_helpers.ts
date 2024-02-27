import {readEnvFile} from "./env_helpers";
import {logger} from "./logger";

/**
 * The setAllowedFormVariants function.
 *
 * This function ...
 */
const setDisabledFormVariants = (): void => {
  if (process.env.DISABLED_FORM_VARIANTS) return;

  const envLines = readEnvFile();
  const variantsLine = envLines.find(line => line.startsWith('DISABLED_FORM_VARIANTS='));

  if (variantsLine) {

    const variants = variantsLine
      .substring(variantsLine.indexOf('=') + 1)
      .split(',')
      .map(variant => variant.trim().replace(/"/g, ''));

    process.env.DISABLED_FORM_VARIANTS = JSON.stringify(variants);
    console.log('DISABLED_FORM_VARIANTS in the .test_env file:', variants)
    console.log('CALL', getDisabledFormVariants());

  } else {
    console.log('[DISABLED_FORM_VARIANTS has not been set in the .test_env file]')
  }
};

/**
 * The getDisabledFormVariants function.
 *
 * This function ...
 */
const getDisabledFormVariants = (): string[] => {
  if (!process.env.DISABLED_FORM_VARIANTS) return [];
  return JSON.parse(process.env.DISABLED_FORM_VARIANTS);
};

/**
 *
 * @param applications
 */
const filterOutDisabledFormVariants = (applications: any): any => {
  // Parse the disabled form variants from the environment variable.
  const disabledFormVariants = getDisabledFormVariants();

  // Return unmodified applications if there are no disabled variants.
  if (!disabledFormVariants) return applications;

  Object.keys(applications).forEach(applicationId => {
    // Iterate over each form variant within the current application.
    Object.keys(applications[applicationId]).forEach(formVariant => {
      // Check if the current variant is among the disabled variants.
      if (disabledFormVariants.includes(formVariant)) {
        // If so, delete this form variant from the application.
        console.log('IN APPLICATION ID ', applicationId)
        console.log('Deleted variant', formVariant)
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

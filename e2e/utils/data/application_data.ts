import {
  privatePersonApplications as originalPrivatePersonApplications
} from "./application/application_data_private_person";
import {
  registeredCommunityApplications as originalRegisteredCommunityApplications
} from "./application/application_data_registered_community";
import {
  unRegisteredCommunityApplications as originalUnRegisteredCommunityApplications
} from "./application/application_data_unregistered_community";
import {
  setDisabledFormVariants,
  filterOutDisabledFormVariants
} from "../form_variant_helpers";

/**
 * Call setDisabledFormVariants().
 *
 * This needs to be done here, since doing it in, for example,
 * global.setup.ts is considered to be "too late". We need to determine
 * what tests we are going to run before we give them to Playwrights test()
 * function. Otherwise, Playwright will complain about missing tests.
 */
setDisabledFormVariants();

const privatePersonApplications = filterOutDisabledFormVariants(originalPrivatePersonApplications);
const registeredCommunityApplications = filterOutDisabledFormVariants(originalRegisteredCommunityApplications);
const unRegisteredCommunityApplications = filterOutDisabledFormVariants(originalUnRegisteredCommunityApplications);

export {
  privatePersonApplications,
  registeredCommunityApplications,
  unRegisteredCommunityApplications
}

import {
  privatePersonApplications as originalPrivatePersonApplications
} from "./application/application_data_private_person";
import {
  registeredCommunityApplications as originalRegisteredCommunityApplications
} from "./application/application_data_registered_community";
import {
  unRegisteredCommunityApplications as originalUnRegisteredCommunityApplications
} from "./application/application_data_unregistered_community";
import {filterOutDisabledFormVariants} from "../form_variant_helpers";

const privatePersonApplications = filterOutDisabledFormVariants(originalPrivatePersonApplications);
const registeredCommunityApplications = filterOutDisabledFormVariants(originalRegisteredCommunityApplications);
const unRegisteredCommunityApplications = filterOutDisabledFormVariants(originalUnRegisteredCommunityApplications);

export {
  privatePersonApplications,
  registeredCommunityApplications,
  unRegisteredCommunityApplications
}

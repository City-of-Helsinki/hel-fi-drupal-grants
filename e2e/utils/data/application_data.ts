import {
  privatePersonApplications as originalPrivatePersonApplications
} from "./application/application_data_private_person";
import {
  registeredCommunityApplications as originalRegisteredCommunityApplications
} from "./application/application_data_registered_community";
import {
  unRegisteredCommunityApplications as originalUnRegisteredCommunityApplications
} from "./application/application_data_unregistered_community";
import {getFormVariantsForTests} from "../form_variant_helpers";

const privatePersonApplications = getFormVariantsForTests(originalPrivatePersonApplications);
const registeredCommunityApplications = getFormVariantsForTests(originalRegisteredCommunityApplications);
const unRegisteredCommunityApplications = getFormVariantsForTests(originalUnRegisteredCommunityApplications);

export {
  privatePersonApplications,
  registeredCommunityApplications,
  unRegisteredCommunityApplications
}

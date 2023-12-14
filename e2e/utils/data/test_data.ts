import {
  profileDataUnregisteredCommunity,
  profileDataPrivatePerson,
  profileDataRegisteredCommunity
} from './profile_data'

const TEST_IBAN = "FI31 4737 2044 0000 48"
const TEST_SSN = "090797-999P"
const TEST_USER_UUID = "13cb60ae-269a-46da-9a43-da94b980c067"

interface Selector {
  type: string;
  name: string;
  value: string;
  resultValue?: string;
}

interface MultiValueField {
  buttonSelector: Selector;
  items: Array<Array<FormField>>;
  expectedErrors?: Object;
}

interface FormField {
  label?: string;
  role: string;
  selector: Selector;
  value?: string;
  multi?: MultiValueField;
}

interface FormData {
  title: string;
  formSelector: string;
  formPath: string;
  formPages: Array<Array<FormField>>;
  expectedDestination: string;
  expectedErrors: Object;
}

interface ProfileData {
  success: FormData
}


const applicationData = {}

export {
  TEST_IBAN,
  TEST_SSN,
  TEST_USER_UUID,
  profileDataPrivatePerson,
  profileDataUnregisteredCommunity,
  profileDataRegisteredCommunity,
  applicationData,
  FormData,
  MultiValueField,
  FormField,
  Selector
}

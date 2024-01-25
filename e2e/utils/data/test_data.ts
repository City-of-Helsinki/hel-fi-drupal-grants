import {
  profileDataUnregisteredCommunity,
  profileDataPrivatePerson,
  profileDataRegisteredCommunity,
  PROFILE_FILE_PATH
} from './profile_data'
import {Page} from "@playwright/test";

const TEST_IBAN = "FI31 4737 2044 0000 48"
const TEST_SSN = "090797-999P"
const TEST_USER_UUID = "13cb60ae-269a-46da-9a43-da94b980c067"

interface SelectorDetails {
  role?: string;
  label?: string;
  text?: string;
  options?: {
    name?: string;
    exact?: boolean;
  };
}

type PartialFormFieldWithRemove = Partial<FormFieldWithRemove>;


interface Selector {
  type: string;
  label?: string;
  name: string;
  value?: string;
  details?: SelectorDetails;
  resultValue?: string;
}

interface MultiValueField {
  buttonSelector: Selector;
  items: Array<Array<FormField>>;
  expectedErrors?: Object;
}

interface DynamicMultiValueField {
  radioSelector: Selector;
  revealedElementSelector: Selector;
  multi_field: MultiValueField;
  expectedErrors?: Object;
}

interface DynamicSingleValueField {
  radioSelector: Selector;
  revealedElementSelector: Selector;
  items: Array<{
    [pageKey: string]: FormField;
  }>;
  expectedErrors?: Object;
}


interface FormField {
  label?: string;
  role?: string;
  selector?: Selector;
  value?: string;
  multi?: MultiValueField;
  dynamic_single?: DynamicSingleValueField;
  dynamic_multi?: DynamicMultiValueField;
}

type RemoveList = string[];

type ViewPageFormatterFunction = (param: string) => string;

interface FormFieldWithRemove extends FormField {
  type?: string;
  label?: string;
  role?: string;
  selector?: Selector;
  value?: string;
  multi?: MultiValueField;
  dynamic_single?: DynamicSingleValueField;
  dynamic_multi?: DynamicMultiValueField;
  viewPageSelector?: string;
  viewPageFormatter?: ViewPageFormatterFunction
  viewPageSkipValidation?: boolean;
}

type FormItems = {
  [itemKey: string]: Partial<FormFieldWithRemove>;
};

interface FormDataWithRemove extends FormData {
  formPages: {
    [pageKey: string]: {
      items: {
        [itemKey: string]: FormFieldWithRemove;
      };
      itemsToRemove?: RemoveList | undefined;
    };
  };
}

// Make formSelector and formPath optional in FormDataWithRemove
type FormDataWithRemoveOptionalProps =
  Partial<Pick<FormDataWithRemove, 'formSelector' | 'formPath'>>
  & Omit<FormDataWithRemove, 'formSelector' | 'formPath'>;

interface FormPage {
  items: FormItems;
  itemsToRemove?: RemoveList | undefined;
}

interface FormData {
  title: string;
  formSelector: string;
  formPath: string;
  formPages: {
    [pageKey: string]: FormPage;
  };
  expectedDestination: string;
  expectedErrors: {}
}

interface ProfileData {
  success: FormData
}

// Type guard for MultiValueField
function isMultiValueField(value: any): value is MultiValueField {
  return typeof value === 'object' && value !== null /* Add more conditions if needed */;
}

// Type guard for DynamicValueField
function isDynamicMultiValueField(value: any): value is DynamicMultiValueField {
  return typeof value === 'object' && value !== null /* Add more conditions if needed */;
}

// Type guard for DynamicValueField
function isDynamicSingleValueField(value: any): value is DynamicSingleValueField {
  return typeof value === 'object' && value !== null /* Add more conditions if needed */;
}

interface PageHandlers {
  [key: string]: (page: Page, formData: FormPage) => Promise<void>;
}


const applicationData = {}

export {
  PROFILE_FILE_PATH,
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
  Selector,
  isMultiValueField,
  DynamicSingleValueField,
  DynamicMultiValueField,
  isDynamicMultiValueField,
  isDynamicSingleValueField,
  FormItems,
  FormDataWithRemove,
  FormFieldWithRemove,
  FormDataWithRemoveOptionalProps,
  PageHandlers,
  FormPage
}

import {
  profileDataUnregisteredCommunity,
  profileDataPrivatePerson,
  profileDataRegisteredCommunity,
  PROFILE_FILE_PATH
} from './profile_data'
import {Page} from "@playwright/test";

interface SelectorDetails {
  role?: string;
  label?: string;
  text?: string;
  options?: {
    name?: string;
    exact?: boolean;
  };
}

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
  multi: MultiValueField;
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
  viewPageSelector?: string;
  viewPageFormatter?: ViewPageFormatterFunction
  viewPageSkipValidation?: boolean;
}

type RemoveList = string[];

type HiddenItemsList = string[];

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
}

type FormItems = {
  [itemKey: string]: Partial<FormFieldWithRemove>;
};

interface FormFieldWithSwap extends FormField {
  swap_value: string;
  view_page_classes: string[];
}

type FormItemsWithSwap = {
  [itemKey: string]: Partial<FormFieldWithSwap>;
};

interface FormDataWithRemove extends FormData {
  formPages: {
    [pageKey: string]: {
      items: {
        [itemKey: string]: FormFieldWithRemove;
      };
      itemsToRemove?: RemoveList | undefined;
      itemsToBeHidden?: HiddenItemsList | undefined;
      itemsToSwap?: FormItemsWithSwap | undefined;
    };
  };
}

type FormDataWithRemoveOptionalProps =
  Partial<Pick<FormDataWithRemove, 'formSelector' | 'formPath'>>
  & Omit<FormDataWithRemove, 'formSelector' | 'formPath'>;

interface FormPage {
  items: FormItems;
  itemsToRemove?: RemoveList | undefined;
  itemsToBeHidden?: HiddenItemsList | undefined;
  itemsToSwap?: FormItemsWithSwap | undefined;
}

interface FormData {
  title: string;
  formSelector: string;
  formPath: string;
  formPages: {
    [pageKey: string]: FormPage;
  };
  expectedDestination?: string;
  expectedErrors: {},
  viewPageSkipValidation?: boolean,
  testFormCopying?: boolean,
  testBankAccountSwap?: boolean,
}

interface PageHandlers {
  [key: string]: (page: Page, formData: FormPage) => Promise<void>;
}

// Type guard for MultiValueField
function isMultiValueField(value: any): value is MultiValueField {
  return typeof value === 'object' && value !== null /* Add more conditions if needed */;
}

// Type guard for DynamicValueField
function isDynamicMultiValueField(value: any): value is DynamicMultiValueField {
  return typeof value === 'object' && value !== null /* Add more conditions if needed */;
}

export {
  PROFILE_FILE_PATH,
  profileDataPrivatePerson,
  profileDataUnregisteredCommunity,
  profileDataRegisteredCommunity,
  FormData,
  MultiValueField,
  FormField,
  Selector,
  DynamicSingleValueField,
  DynamicMultiValueField,
  FormItems,
  FormDataWithRemove,
  FormFieldWithRemove,
  FormDataWithRemoveOptionalProps,
  PageHandlers,
  FormPage,
  isMultiValueField,
  isDynamicMultiValueField,
}

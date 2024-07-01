import {
  profileDataUnregisteredCommunity,
  profileDataPrivatePerson,
  profileDataRegisteredCommunity,
  PROFILE_FILE_PATH
} from './profile_data'
import {Page} from "@playwright/test";

/**
 * The following interfaces and types are used to define the structure of the test data
 * and tests that are used to test the applications on the website.
 *
 * @file Provides interfaces and types for tests and test data.
 */

/**
 * Interface for a selector object.
 * Used in test data to define selectors for form fields.
 */
interface Selector {
  type: string;
  label?: string;
  name: string;
  value?: string;
  details?: SelectorDetails;
  resultValue?: string;
}

/**
 * Interface for selector details.
 * Used by the Selector interface.
 */
interface SelectorDetails {
  role?: string;
  label?: string;
  text?: string;
  options?: {
    name?: string;
    exact?: boolean;
  };
}

/**
 * Interface for a form field object.
 * Used by the fields in the form data.
 */
interface FormField {
  label?: string;
  role?: string;
  selector?: Selector;
  value?: string;
  multi?: MultiValueField;
  dynamic_single?: DynamicSingleValueField;
  dynamic_multi?: DynamicMultiValueField;
  viewPageSelector?: string;
  viewPageSelectors?: string[];
  viewPageFormatter?: ViewPageFormatterFunction
  viewPageSkipValidation?: boolean;
  printPageSkipValidation?: boolean;
}

/**
 * Interface for a form field object with a removal option.
 * Used by the fields in the form data.
 */
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

/**
 * Interface for a multi-value field object.
 * Used by various fields in the form data.
 */
interface MultiValueField {
  buttonSelector: Selector;
  items: Array<Array<FormField>>;
  expectedErrors?: Object;
}

/**
 * Interface for a dynamic multi-value field object.
 * Used by various fields in the form data.
 */
interface DynamicMultiValueField {
  radioSelector: Selector;
  revealedElementSelector: Selector;
  multi: MultiValueField;
  expectedErrors?: Object;
}

/**
 * Interface for a dynamic single-value field object.
 * Used by various fields in the form data.
 */
interface DynamicSingleValueField {
  radioSelector: Selector;
  revealedElementSelector: Selector;
  items: Array<{
    [pageKey: string]: FormField;
  }>;
  expectedErrors?: Object;
}

/**
 * Interface for a page handler object.
 * Used to define the page handlers for the form data.
 */
interface PageHandlers {
  [key: string]: (page: Page, formData: FormPage) => Promise<void>;
}

/**
 * Interface for a form page object.
 * Used to define the form pages in the form data.
 */
interface FormPage {
  items: FormItems;
  itemsToRemove?: RemoveList | undefined;
  itemsToBeHidden?: HiddenItemsList | undefined;
  itemsToSwap?: FieldSwapItemList | undefined;
  tooltipsToValidate?: TooltipsList| undefined;
  expectedInlineErrors?: ExpectedInlineError[] | undefined;
}

/**
 * Type for a collection of form items.
 * Used to define the form items in the form page.
 */
type FormItems = {
  [itemKey: string]: Partial<FormFieldWithRemove>;
};

/**
 * Interface for a form data object.
 * Used to define the form data for the tests.
 */
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
  testFieldSwap?: boolean,
  validatePrintPage?: boolean,
  validateTooltips?: boolean,
}

/**
 * Interface for a form data object with a removal option.
 * Used to define the form data for the tests.
 */
interface FormDataWithRemove extends FormData {
  formPages: {
    [pageKey: string]: {
      items: {
        [itemKey: string]: FormFieldWithRemove;
      };
      itemsToRemove?: RemoveList | undefined;
      itemsToBeHidden?: HiddenItemsList | undefined;
      itemsToSwap?: FieldSwapItemList | undefined;
      tooltipsToValidate?: TooltipsList| undefined;
      expectedInlineErrors?: ExpectedInlineError[] | undefined;
    };
  };
}

type FormDataWithRemoveOptionalProps =
  Partial<Pick<FormDataWithRemove, 'formSelector' | 'formPath'>>
  & Omit<FormDataWithRemove, 'formSelector' | 'formPath'>;

/**
 * Type for a remove list.
 * Used to define the list of items to be
 * removed from the form in itemsToRemove.
 */
type RemoveList = string[];

/**
 * Type for a hidden items list.
 * Used to define the list of items to be
 * hidden in itemsToBeHidden.
 */
type HiddenItemsList = string[];

/**
 * Type for expected inline errors.
 * Used to define the expected inline errors in
 * the form data in expectedInlineErrors.
 */
type ExpectedInlineError = {
  selector: string;
  errorMessage: string;
}
/**
 * Type for a field swap item list.
 * Used to define the list of items to be swapped in itemsToSwap.
 */
type FieldSwapItemList = FieldSwapItem[];

/**
 * Type for a field swap item.
 * Used to define the items to be swapped in itemsToSwap.
 */
type FieldSwapItem = {
  field: string;
  swapValue: string;
};

/**
 * Type for a list of tooltips.
 * Used to define the list of tooltips to be validated in tooltipsToValidate.
 */
type TooltipsList = Tooltip[];

/**
 * Interface for a tooltip object.
 * Used to define the tooltip in tooltipsList.
 */
type Tooltip = {
  aria_label: string;
  message: string;
};

/**
 * Type for a view page formatter function.
 * Used to format the raw input data to a view page format.
 */
type ViewPageFormatterFunction = (param: string) => string;

/**
 * Interface for a collection of pages.
 * These page collections are used to define the test scenarios
 * in the public smoke tests.
 */
interface PageCollection {
  [key: string]: TestScenario;
}

/**
 * Interface for a test scenario object.
 * Used to define the test scenarios in the public smoke tests.
 */
interface TestScenario {
  url: string;
  validatePageTitle: boolean,
  components: ComponentDetails[];
}

/**
 * Interface for component details.
 * Used to define the component details in the public smoke tests.
 */
interface ComponentDetails {
  containerClass: string;
  elements: ElementDetails[];
  occurrences?: number;
}

/**
 * Interface for element details.
 * Used to define the element details in the public smoke tests.
 */
interface ElementDetails {
  selector: string;
  countExact?: number;
  countAtLeast?: number;
  expectedText?: string[];
}

// Type guard for MultiValueField.
function isMultiValueField(value: any): value is MultiValueField {
  return typeof value === 'object' && value !== null /* Add more conditions if needed */;
}

// Type guard for DynamicValueField.
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
  FieldSwapItemList,
  ComponentDetails,
  ElementDetails,
  TestScenario,
  PageCollection,
  TooltipsList,
  Tooltip,
  ExpectedInlineError,
  isMultiValueField,
  isDynamicMultiValueField,
}

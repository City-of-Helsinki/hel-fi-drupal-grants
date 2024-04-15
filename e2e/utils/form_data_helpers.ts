import {FormData, FormPage} from "./data/test_data";
import cloneDeep from "lodash.clonedeep";

/**
 * The createFormData function.
 *
 * This function takes in a base form (baseFormData)
 * and merges it with a partial overrides form (overrides).
 * Any fields under itemsToRemove or itemsToBeHidden will
 * also be removed from the newly created form.
 *
 * The function uses the lodash cloneDeep utility function
 * for cloning the "items" part of the form, in order
 * to perform a deep copy.
 *
 * @docs https://developer.mozilla.org/en-US/docs/Glossary/Deep_copy
 *
 * @param baseFormData
 *   The base form.
 * @param overrides
 *   The parts we want to override.
 */
function createFormData(baseFormData: FormData, overrides: Partial<FormData>): FormData {

  const formPages = Object.keys(baseFormData.formPages).reduce((result, pageKey) => {

    result[pageKey] = {
      ...baseFormData.formPages[pageKey],
      ...(overrides.formPages && overrides.formPages[pageKey]),
      items: {
        ...cloneDeep(baseFormData.formPages[pageKey].items),
        ...(overrides.formPages && overrides.formPages[pageKey] && overrides.formPages[pageKey].items),
      },
    };

    if (!overrides.formPages || !overrides.formPages[pageKey]) {
      return result;
    }

    // Remove any fields under itemsToRemove.
    overrides.formPages[pageKey].itemsToRemove?.forEach((itemToRemove: string) => {
      const multiValueKeyInfo = parseMultiValueKey(itemToRemove);

      // If the field is not a multi-value field, then just delete it normally.
      if (!multiValueKeyInfo) {
        return delete result[pageKey].items[itemToRemove];
      }

      /**
       * Now we know the field is either a dynamic multi-value or a normal multi-value field.
       * We can't know which one it is, so we have to check for both. Then we
       * filter out the item inside the multi-value field with a matching selector,
       * thereby removing it.
       */
      const { baseName, index, subItemKey } = multiValueKeyInfo;
      const dynamicMultiValueItems = result[pageKey]?.items[baseName]?.dynamic_multi?.multi?.items;
      const multiValueItems = result[pageKey]?.items[baseName]?.multi?.items;
      const multiItems = dynamicMultiValueItems || multiValueItems;

      if (multiItems && multiItems[index]) {
        multiItems[index] = multiItems[index].filter((item: any) => {
          return item.selector?.value !== `${baseName}-items-[INDEX]-item-${subItemKey}`;
        });
      }
    });

    // Remove any fields under itemsToBeHidden.
    overrides.formPages[pageKey].itemsToBeHidden?.forEach((itemToBeHidden: string) => {
      delete result[pageKey].items[itemToBeHidden];
    });

    return result;

  }, {} as { [pageKey: string]: FormPage });

  return {
    ...baseFormData,
    ...overrides,
    formPages,
  };
}

/**
 * The parseMultiValueKey function.
 *
 * This function attempts to parse out a
 * baseName, index and subItemKey form a form field key.
 * If all three variables are found, then we know
 * the key represents a multi-value field.
 *
 * Ex1: edit-hanke-alkaa
 * This would return null.
 *
 * Ex2: edit-myonnetty-avustus-items-0-item-issuer
 * This would return {edit-myonnetty-avustus, 0, issuer}.
 *
 * @param key
 *   The key we are parsing.
 *
 * @return { {baseName: string, index: number, subItemKey: string} | null }
 */
const parseMultiValueKey = (key: string): { baseName: string, index: number, subItemKey: string } | null => {
  const match = key.match(/^(.+)-items-(\d+)-item-(.+)$/);
  if (match && match.length === 4) {
    return {
      baseName: match[1],
      index: parseInt(match[2], 10),
      subItemKey: match[3]
    };
  }
  return null;
};

export {
  createFormData,
}

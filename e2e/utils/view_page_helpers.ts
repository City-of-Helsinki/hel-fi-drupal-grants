/**
 * Provides viewPage helper functions.
 *
 * These functions are helper functions for
 * the "View" page.
 *
 * @file Provides viewPage helper functions.
 */

/**
 * The viewPageBuildSelectorForItem function.
 *
 * This function takes in a form item key and
 * formats it to a class that can be used for
 * targeting said item on the "View" page.
 * The formatting does the following:
 *
 * Ex1: edit-acting-year
 * => .form-item-acting-year.
 *
 * Ex2: edit-kyseessa-on-festivaali-tai-tapahtuma-0"
 * => .form-item-kyseessa-on-festivaali-tai-tapahtuma".
 *
 * Ex3: edit-talousarvio-attachment-upload"
 * => .form-item-talousarvio".
 *
 * @param {string} itemKey
 *   The key we are formatting.
 */
const viewPageBuildSelectorForItem = (itemKey: string): string => {
  const parts = itemKey.split('-');
  const excludedParts = ['edit', 'attachment', 'upload'];

  // Filter out various keywords from the item key, since they are
  // usually not present in the constructed class. Also remove any numbers
  // from the end.
  const filteredParts = parts.filter((part, index) =>
    !excludedParts.includes(part) &&
    !(!isNaN(Number(part)) && index === parts.length - 1)
  );
  return '.form-item-' + filteredParts.join('-');
}

export {
  viewPageBuildSelectorForItem
}

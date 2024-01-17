import path from "path";
import {logger} from "./logger";

/**
 * Provides viewPageFormat functions.
 *
 * These functions are viewPageFormat functions.
 * The intention of these functions is to work as a callback
 * for input form data. The form data is transformed so that
 * the input matches the output when viewing a submitted
 * form on the "View" page.
 *
 * @file Provides viewPageFormat functions.
 */

/**
 * The viewPageFormatNumber function.
 *
 * This function formats numbers so that they include
 * the correct separators. For example, 5709,98 is
 * formatted to 5 709,98.
 *
 * @param {string} number
 *   The number that needs formatting.
 */
const viewPageFormatNumber = (number: string): string => {
  try {
    number = number.replace(',', '.');
    number = parseFloat(number).toLocaleString('fi', {minimumFractionDigits: 2 });
    // Replace non-breaking space (ASCII code 160) with regular space (ASCII code 32).
    number = number.replace(/\u00A0/g, String.fromCharCode(32));
    number = number.trim();
    return number;
  } catch (error) {
    logger("Error parsing number:", number)
    return number;
  }
};

/**
 * The viewPageFormatNumber function.
 *
 * This functions formats numerical booleans (1 or 0)
 * to either "Kyllä" or "Ei".
 *
 * @param {string} booleanString
 *   The boolean we are formatting.
 */
const viewPageFormatBoolean = (booleanString: string): string => {
  return booleanString === '1' ? 'Kyllä' : 'Ei';
};

/**
 * The viewPageFormatNumber function.
 *
 * This function formats dats to the desired format.
 * An input date of "2023-11-01" will return "01.11.2023".
 *
 * @param {string} date
 *   The date we are formatting.
 */
const viewPageFormatDate = (date: string): string => {
  try {
    const inputDate = new Date(date);
    const options: Intl.DateTimeFormatOptions = { day: '2-digit', month: 'numeric', year: 'numeric' };
    return inputDate.toLocaleDateString('fi-FI', options);
  } catch (error) {
    logger("Error parsing date:", date)
    return date;
  }
};

/**
 * The viewPageFormatFilePath function.
 *
 * This function formats a filepath to a file name.
 * Ex: The input "/Users/alexander/Projects/hel-fi-drupal-grants/e2e/utils/data/test.pdf"
 * would return "test".
 *
 * @param {string} filePath
 *   The filepath we are formatting.
 */
const viewPageFormatFilePath = (filePath: string): string => {
  try {
    return path.basename(filePath, path.extname(filePath));
  } catch (error) {
    logger("Error parsing filepath:", filePath)
    return filePath;
  }
}

/**
 * The viewPageFormatAddress function.
 *
 * This function formats an address
 * by removing any commas (",") from it.
 *
 * @param {string} address
 *   The filepath we are formatting.
 */
const viewPageFormatAddress = (address: string): string => {
  return address.replace(/,/g, '');
}

export {
  viewPageFormatNumber,
  viewPageFormatBoolean,
  viewPageFormatDate,
  viewPageFormatFilePath,
  viewPageFormatAddress
};

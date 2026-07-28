import {expect, Page, test} from '@playwright/test';
import {logger} from './logger';
import {logCurrentUrl} from './helpers';
import {clickButton, fillFormField} from './input_helpers';
import {validateFormErrors} from './error_validation_helpers';
import {navigateAndValidateProfilePage} from './validation_helpers';
import {PROFILE_RESET_DATA, ProfileEditField} from './data/profile_edit_data';
import {Selector} from './data/test_data';

const PROFILE_FORM_PATH = '/fi/oma-asiointi/hakuprofiili/muokkaa';
const PROFILE_PAGE_PATH = '/fi/oma-asiointi/hakuprofiili';
const PROFILE_DATA_SELECTOR = '.grants-profile--extrainfo';

const SUBMIT_BUTTON_SELECTOR: Selector = {
  type: 'data-drupal-selector',
  name: 'data-drupal-selector',
  value: 'edit-actions-submit',
};

/**
 * Edit and validate the profile field values.
 *
 * This function edits the given profile fields by:
 * 1. Reading the original values from the profile form.
 * 2. Saving the form with the edited values.
 * 3. Validating that the edited values replaced the original ones.
 *
 * The original values are returned so that they can be
 * restored by revertProfileFields.
 *
 * @param page
 *   Playwright page object.
 * @param profileType
 *   The profile type we are editing.
 * @param fields
 *   The fields we are editing.
 *
 * @return Promise<Record<string, string>>
 *   The original values keyed by field selector.
 */
const editProfileFields = async (
  page: Page,
  profileType: string,
  fields: ProfileEditField[]
): Promise<Record<string, string>> => {
  logger(`Editing profile data for: ${profileType}.`);
  await openProfileEditForm(page, profileType);

  const originalValues = await readProfileFieldValues(page, fields);
  const emptyFields = Object.entries(originalValues)
    .filter(([, value]) => !value)
    .map(([selector]) => selector);

  // Skip the test if the profile is missing data, since it can't be restored.
  if (emptyFields.length) {
    logger(`Profile has empty fields: ${emptyFields.join(', ')}.`);
    test.skip(true, 'Skip profile edit test. The profile has empty fields.');
  }

  const editedValues = getEditedProfileFieldValues(fields);
  await saveProfileFields(page, editedValues);
  await validateProfileFields(page, profileType, editedValues);

  return originalValues;
}

/**
 * Restore the original profile values and validate them.
 *
 * @param page
 *   Playwright page object.
 * @param profileType
 *   The profile type we are editing.
 * @param originalValues
 *   The original values keyed by field selector.
 */
const revertProfileFields = async (
  page: Page,
  profileType: string,
  originalValues: Record<string, string>
) => {
  logger(`Reverting profile data for: ${profileType}.`);
  await openProfileEditForm(page, profileType);
  await saveProfileFields(page, originalValues);
  await validateProfileFields(page, profileType, originalValues);
}

/**
 * Is profile reset enabled.
 *
 * @return boolean
 *   TRUE if RESET_PROFILE is set to 'TRUE'.
 */
const isProfileResetEnabled = (): boolean => {
  return process.env.RESET_PROFILE === 'TRUE';
}

/**
 * Restore the profile fields values to the original values.
 *
 * @param page
 *   Playwright page object.
 * @param profileType
 *   The profile type we are resetting.
 */
const resetProfileFields = async (page: Page, profileType: string) => {
  logger(`Resetting profile data for: ${profileType}.`);
  const resetValues = getEditedProfileFieldValues(PROFILE_RESET_DATA[profileType]);

  await openProfileEditForm(page, profileType);
  await saveProfileFields(page, resetValues);
  await validateProfileFields(page, profileType, resetValues);
}

/**
 * Open profile edit form and validate the right form was reached.
 *
 * @param page
 *   Playwright page object.
 * @param profileType
 *   The profile type we are editing.
 */
const openProfileEditForm = async (page: Page, profileType: string) => {
  await page.goto(PROFILE_FORM_PATH);
  await logCurrentUrl(page);

  const formSelector = `grants-role-${profileType}`;
  await expect(page.locator('body'), 'Reached the wrong profile form.').toHaveClass(new RegExp(`\\b${formSelector}\\b`));
  logger(`Reached the profile form: ${formSelector}.`);
}

/**
 * Read the current values of the given fields on the profile form.
 *
 * @param page
 *   Playwright page object.
 * @param fields
 *   The fields we are reading.
 *
 * @return Promise<Record<string, string>>
 *   The found values keyed by field selector.
 */
const readProfileFieldValues = async (
  page: Page,
  fields: ProfileEditField[]
): Promise<Record<string, string>> => {
  const values: Record<string, string> = {};

  for (const field of fields) {
    const locator = page.locator(`[data-drupal-selector="${field.selector}"]`);
    await expect(locator, `Failed to locate the field: ${field.selector}.`).toBeVisible();
    values[field.selector] = await locator.inputValue();
  }

  logger('Original profile data:', false, values);
  return values;
}

/**
 * Get the edited profile field values.
 *
 * @param fields
 *   The fields we are editing.
 *
 * @return Record<string, string>
 *   The edited values keyed by field selector.
 */
const getEditedProfileFieldValues = (fields: ProfileEditField[]): Record<string, string> => {
  const values: Record<string, string> = {};

  for (const field of fields) {
    values[field.selector] = field.value;
  }

  return values;
}

/**
 * Fill the given values on the profile form and save the form.
 *
 * @param page
 *   Playwright page object.
 * @param values
 *   The values we are saving, keyed by field selector.
 */
const saveProfileFields = async (page: Page, values: Record<string, string>) => {
  for (const [selector, value] of Object.entries(values)) {
    await fillFormField(page, {
      role: 'input',
      selector: {
        type: 'data-drupal-selector',
        name: 'data-drupal-selector',
        value: selector,
      },
      value: value,
    }, selector);
  }

  await clickButton(page, SUBMIT_BUTTON_SELECTOR);
  await page.waitForLoadState('load');
  await validateFormErrors(page, {}, '.form-item--error-message');

  const actualPathname = new URL(page.url()).pathname;
  expect(actualPathname, 'Failed to save the profile form.').toBe(PROFILE_PAGE_PATH);
  logger('Profile form saved.');
}

/**
 * Validate the saved profile data.
 *
 * @param page
 *   Playwright page object.
 * @param profileType
 *   The profile type we are validating.
 * @param expectedValues
 *   The values that should be found, keyed by field selector.
 */
const validateProfileFields = async (
  page: Page,
  profileType: string,
  expectedValues: Record<string, string>
) => {
  await navigateAndValidateProfilePage(page, profileType);

  const profileData = await page.locator(PROFILE_DATA_SELECTOR).textContent();
  const validationErrors: string[] = [];

  for (const [selector, value] of Object.entries(expectedValues)) {
    if (!profileData?.includes(value)) {
      validationErrors.push(`Value "${value}" of "${selector}" not found on the profile page.`);
    }
  }

  expect(validationErrors).toEqual([]);

  await openProfileEditForm(page, profileType);
  for (const [selector, value] of Object.entries(expectedValues)) {
    const locator = page.locator(`[data-drupal-selector="${selector}"]`);
    await expect(locator, `Wrong value in the field: ${selector}.`).toHaveValue(value);
  }

  logger('Profile data validated.');
}

export {
  editProfileFields,
  revertProfileFields,
  resetProfileFields,
  isProfileResetEnabled,
}

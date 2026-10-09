import { expect, type Locator, type Page, test } from '@playwright/test';
import { fakerFI as faker } from '@faker-js/faker';
import { buildFormTree, conditionVerdict, type FormTree, type StepField } from './stepInspector';
import type { FormPreviewResponse } from './schemaFetcher';
import {
  fillApplicantInfoStep,
  verifyApplicantInfoStepFieldTranslations
} from './applicantInfoStep';
import {
  ATTACHMENT_CHECKBOX_LABELS,
  assertFieldErrorGone,
  assertMissingInputsGone,
  assertMissingInputsVisible,
  clickNext,
  clickOnStep,
  clickOnStepWithTitle,
  createTranslator,
  gatherRequiredFieldWarnings,
  nextAttachmentMode,
  resetAttachments,
  saveDraft,
  waitForForm,
  waitForFormLoad,
  uploadAttachments,
} from './utils';
import {
  finnishDate, selectFirstDropdownOption,
} from './fieldFillers'
import { logger } from "../logger";
import { logCurrentUrl, waitForTextWithInterval } from "../helpers";

/**
 * A map of field IDs to the values entered during the Finnish fill pass.
 * Used later to verify those same values appear in other languages
 * and in the form preview.
 */
export type FilledFields = Map<string, string>;

/**
 * A custom fill value: a plain string, or a function returning one,
 * so an author can supply a faker expression like () => faker.lorem.words(30).
 */
export type FieldInputValue = string | (() => string);

/**
 * Custom fill values for a form, nested to mirror the form tree:
 * step -> section -> field. A leaf value replaces the auto-generated value
 * for that field. Example:
 *  information_in_more_detail_step: {
 *    grant_target_section: {
 *      safety_practices: 'text'
 *    }
 *  }
 */
export type FieldInputs = { [key: string]: FieldInputValue | FieldInputs };

/**
 * Looks up a field's custom value by walking its path through the nested
 * fieldInputs tree.
 *
 * @param fieldInputs
 *   The nested custom values for the form.
 * @param fieldPath
 *   The field's path segments, e.g. [step, section, field].
 */
function resolveFieldInput(
  fieldInputs: FieldInputs | undefined,
  fieldPath: string[],
): FieldInputValue | undefined {
  let node: FieldInputValue | FieldInputs | undefined = fieldInputs;
  for (const segment of fieldPath) {
    if (!node || typeof node !== 'object') return undefined;
    node = node[segment];
  }
  return typeof node === 'string' || typeof node === 'function' ? node : undefined;
}

/**
 * Context passed to a field-specific logic handler.
 */
export type FieldLogicContext = {
  page: Page;
  field: StepField;
  fieldId: string;
  shouldFill: boolean;
  t: (key: string) => string;
  filledFields?: FilledFields;
};

/**
 * Handles custom logic for a single field.
 *
 * Returning TRUE indicates that the field was handled and generic processing
 * should be skipped.
 */
export type FieldLogicHandler = (ctx: FieldLogicContext) => Promise<boolean>;

/**
 * The answer to give to a question that shows other fields.
 */
export type FormAnswer = string | number | boolean;

/**
 * Custom form logic for fields that the generic engine cannot handle.
 *
 * The structure is nested by field path, like FieldInputs. Handler functions
 * are placed at "leaf" nodes. Use this only for true one-off widgets or
 * interactions; recurring patterns belong in the engine. A plain value at a
 * leaf is the answer the engine gives to a question that shows other fields.
 */
export type FormLogic = { [key: string]: FieldLogicHandler | FormAnswer | FormLogic };

/**
 * Resolves a custom logic handler for a field path.
 *
 * Walks the nested formLogic tree using the given field path segments.
 *
 * @param formLogic
 *   The nested custom logic for the form.
 * @param fieldPath
 *   The field path segments, for example [step, section, field].
 */
function resolveFormLogic(
  formLogic: FormLogic | undefined,
  fieldPath: string[],
): FieldLogicHandler | undefined {
  let node: FieldLogicHandler | FormLogic | undefined = formLogic;
  for (const segment of fieldPath) {
    if (!node || typeof node !== 'object') return undefined;
    node = node[segment];
  }
  return typeof node === 'function' ? node : undefined;
}

/**
 * Collect the answers set in formLogic, keyed by field path.
 *
 * @param formLogic
 *   The nested custom logic for the form.
 * @param prefix
 *   The path segments accumulated so far.
 * @param answers
 *   The map the answers are collected into.
 */
function collectFormAnswers(
  formLogic: FormLogic | undefined,
  prefix: string[] = [],
  answers = new Map<string, unknown>(),
): Map<string, unknown> {
  for (const [key, value] of Object.entries(formLogic ?? {})) {
    if (typeof value === 'object') {
      collectFormAnswers(value, [...prefix, key], answers);
    } else if (typeof value !== 'function') {
      answers.set([...prefix, key].join('.'), value);
    }
  }
  return answers;
}

/**
 * Options shared by the main form test functions.
 */
export type VerifyFormFieldsOptions = {
  /** Defaults to ['fi', 'sv', 'en'] */
  languages?: string[];
  /** Form URL */
  formURL?: string;
  /** Form completion URL */
  formCompletionURL?: string;
  /** Map of filled fields */
  filledFields?: FilledFields;
  /** Custom fill values, keyed by field id. */
  fieldInputs?: FieldInputs;
  /** Custom field logic keyed by field path. */
  formLogic?: FormLogic;
};

/**
 * Text shown on the application completion page after a successful submit.
 */
const COMPLETION_TEXT: Record<'heading' | 'sent' | 'received', Record<string, string>> = {
  heading: {
    en: 'Grant application sent successfully',
    fi: 'Avustushakemus lähetetty onnistuneesti',
    sv: 'Anslagsansökan har skickats'
  },
  sent: {
    en: 'Sent - waiting for confirmation',
    fi: 'Lähetetty - odotetaan vahvistusta',
    sv: 'Skickad - väntar på bekräftelse'
  },
  received: {
    en: 'Received',
    fi: 'Vastaanotettu',
    sv: 'Mottagen'
  },
};

/**
 * Resolves a field's type token from its rendered DOM wrapper.
 *
 * Field wrappers include a hdbt-form--field--<token> CSS class, where the
 * token identifies the field type (for example date, decimal-number, or
 * select).
 *
 * @param page
 *   The Playwright page instance.
 * @param fieldId
 *   The field DOM id.
 *
 * @returns
 *   The field type token, or null if it cannot be resolved.
 */
async function getFieldTypeToken(page: Page, fieldId: string): Promise<string | null> {
  const field = page.locator(`#${fieldId}`);
  if ((await field.count()) === 0) return null;
  const wrapper = field.locator('xpath=ancestor-or-self::*[contains(@class, "hdbt-form--field--")][1]');
  if ((await wrapper.count()) === 0) return null;
  const className = (await wrapper.getAttribute('class')) ?? '';
  const prefix = 'hdbt-form--field--';
  const typeClass = className.split(' ').find((c) => c.startsWith(prefix));
  return typeClass ? typeClass.slice(prefix.length) : null;
}

/**
 * Returns true if the input is disabled, waiting briefly for a debounced change.
 *
 * @param input
 *   The input locator.
 * @param timeout
 *   How long to wait for the input to become disabled.
 */
async function waitOptionDisabled(input: Locator, timeout: number): Promise<boolean> {
  try {
    await expect(input).toBeDisabled({ timeout });
    return true;
  } catch {
    return false;
  }
}

/**
 * Find the input of a field, or its first option for radios and subventions.
 *
 * @param page
 *   The Playwright page instance.
 * @param field
 *   The field.
 * @param fieldId
 *   The field DOM id.
 */
function fieldInput(page: Page, field: StepField, fieldId: string): Locator {
  if (field.options?.length) return page.locator(`#${fieldId}-${field.options[0].id}`);
  if (field.widget === 'atvFile') return page.locator(`#${field.fieldName}`);
  if (field.widget === 'radio' || field.widget === 'grantDuration') return page.locator(`[id^="${fieldId}_"]`).first();
  return page.locator(`#${fieldId}`);
}

/**
 * Check that an answer shows and hides the right conditional fields on the step.
 *
 * Only fields that depend on this answer alone are checked.
 *
 * @param page
 *   The Playwright page instance.
 * @param stepFields
 *   All fields on the current step.
 * @param paths
 *   The keys of the answers that were set.
 * @param value
 *   The answer that was given.
 * @param t
 *   The translation function for the current language.
 */
async function expectConditionalFields(
  page: Page,
  stepFields: StepField[],
  paths: string[],
  value: unknown,
  t: (key: string) => string,
): Promise<void> {
  // Rows of a list are added later, so check them only when the answer is in the same row.
  const answerInRow = paths.some((path) => path.includes('.0.'));

  for (const field of stepFields) {
    if (!field.conditions) continue;
    const verdict = conditionVerdict(field.conditions, paths, value);
    const input = fieldInput(page, field, `root_${field.fieldPath.join('_')}`);
    if (verdict === 'hidden') {
      await expect(input, `Form hides "${t(field.titleKey)}" when the answer is ${String(value)}.`).toHaveCount(0);
    } else if (verdict === 'shown' && (!field.isArrayItem || answerInRow)) {
      await expect(input, `Form shows "${t(field.titleKey)}" when the answer is ${String(value)}.`).toBeVisible();
    }
  }
}

/**
 * Handle a single field.
 *
 * Checks that field label, tooltip and description is visible.
 * When shouldFill is true, also types a valid value into each field
 * and stores it in filledFields for later verification.
 *
 * @param page
 * @param field
 * @param fieldId
 * @param fieldTitle
 * @param t
 * @param shouldFill
 * @param stepFields
 * @param addedArrays
 * @param filledFields
 * @param fieldInputs
 * @param usedFieldInputs
 * @param formLogic
 */
async function handleField(
  page: Page,
  field: StepField,
  fieldId: string,
  fieldTitle: string,
  t: (key: string) => string,
  shouldFill: boolean,
  stepFields: StepField[],
  addedArrays: Set<string>,
  filledFields?: FilledFields,
  fieldInputs?: FieldInputs,
  usedFieldInputs?: Set<string>,
  formLogic?: FormLogic,
): Promise<void> {
  // Custom field logic can override the generic engine for a specific field.
  // If the handler reports the field as handled, skip further processing.
  const logicHandler = resolveFormLogic(formLogic, field.fieldPath);
  if (logicHandler && await logicHandler({ page, field, fieldId, shouldFill, t, filledFields })) {
    return;
  }

  // The current field belongs to a repeatable list, like "Applied grants".
  if (field.isArrayItem && field.arrayField && !addedArrays.has(field.arrayField)) {
    const listId = `root_${field.fieldPath.slice(0, field.fieldPath.lastIndexOf('0')).join('_')}`;
    const addButton = page.locator(`#${listId}-add`);
    const fieldElement = page.locator(`#${fieldId}`);

    // Find the list by its add button, which stays on the page when all rows are removed.
    const arrayContainer = addButton.locator('xpath=ancestor::div[contains(concat(" ", @class, " "), " field-array ")][1]');

    // Lists like "Muut samaan tarkoitukseen myönnetyt avustukset" stay hidden
    // on the translation pass until their question is answered.
    if (!shouldFill && field.conditional && await arrayContainer.count() === 0) {
      return;
    }

    // Remove auto-added empty rows left over from the error-gathering pass.
    const errorItems = arrayContainer.locator('.array-item')
      .filter({ has: page.locator('.has-error') })
      .filter({ has: page.locator('.hdbt-form__array-remove') });
    let errorCount = await errorItems.count();
    while (errorCount > 0) {
      logger('Found empty list item, removing it.');
      await errorItems.first().locator('.hdbt-form__array-remove').first().click();
      await expect(errorItems).toHaveCount(errorCount - 1);
      errorCount = await errorItems.count();
    }

    // Add a row only when one is not already present.
    if (await fieldElement.count() === 0) {
      await addButton.click();
    }
    addedArrays.add(field.arrayField);
    if (field.groupDescriptionKey) {
      await expect(
        arrayContainer.locator('.hdbt-form--description').filter({ hasText: t(field.groupDescriptionKey) }).first()
      ).toBeVisible();
    }
  }

  // Fields like "Miten tapahtuma edistää yhdenvertaisuutta ja tasa-arvoa?" show only after some answer,
  // so the fill pass fails when one is missing after its answer is given.
  if (field.conditional) {
    const input = fieldInput(page, field, fieldId);
    if (!shouldFill) {
      if (await input.count() === 0) return;
    } else {
      await expect(input, `Form shows "${t(fieldTitle)}" for the chosen answers.`).toBeVisible();
    }
  }

  // Subvention fields are a set of amount inputs, one per funding
  // option. Each option has its own input, so loop through them all.
  if (field.options?.length) {
    let hasFilledOption = false;
    for (const option of field.options) {
      const optionId = `${fieldId}-${option.id}`;
      const optionInput = page.locator(`#${optionId}`);
      await expect(optionInput).toBeVisible();
      await expect(page.locator(`label[for="${optionId}"]`)).toBeVisible();

      if (!shouldFill) continue;

      // Single-subvention fields allow only one option to have a value.
      if (field.singleSubvention && hasFilledOption) break;

      // A start grant sets one option and disables the rest after debounce,
      // so wait for the disabled state to settle before filling.
      const disabled = field.startGrant
        ? await waitOptionDisabled(optionInput, 3000)
        : await optionInput.isDisabled();
      if (disabled) continue;

      // Fill the input with a random amount.
      const fieldValue = faker.number.int({ min: 1, max: 99999 }).toString();
      const decimal = faker.number.int({ min: 10, max: 99 }).toString();
      await page.fill(`#${optionId}`, `${fieldValue},${decimal}`);
      filledFields?.set(optionId, fieldValue);
      hasFilledOption = true;
    }
    return;
  }

  // When handling a radio-button, pick the chosen answer for questions that
  // open other fields, else the "truthy" option, else the first option.
  if (field.widget === 'radio' || field.widget === 'grantDuration') {
    const isControl = field.controlValue !== undefined;
    const preferredOption = page.locator(`#${fieldId}_${isControl ? String(field.controlValue) : 'true'}`);
    // Options like the three-year duration show only after an amount is filled,
    // so require the chosen answer on the fill pass only.
    const option = (isControl && shouldFill) || (await preferredOption.count()) > 0
      ? preferredOption
      : page.locator(`input[type="radio"][id^="${fieldId}_"]`).first();
    await expect(option).toBeVisible();
    const optionId = (await option.getAttribute('id')) ?? '';
    // Verify the legend of the closest fieldset.
    await expect(option.locator('xpath=ancestor::fieldset[1]/legend')).toContainText(t(fieldTitle));
    if (shouldFill) {
      // Questions like "Onko kyseessä jatkohakemus?" show different fields for each answer,
      // so try the other answers first and check which fields they show and hide.
      if (isControl) {
        for (const otherValue of field.enumValues ?? []) {
          if (otherValue === field.controlValue) continue;
          const otherLabel = page.locator(`label[for="${fieldId}_${String(otherValue)}"]`);
          if (await otherLabel.count() === 0) continue;
          await otherLabel.click();
          await expectConditionalFields(page, stepFields, field.controlPaths ?? [], otherValue, t);
        }
      }
      await page.click(`label[for="${optionId}"]`);
      await assertFieldErrorGone(page, fieldId);
      if (isControl) {
        await expectConditionalFields(page, stepFields, field.controlPaths ?? [], field.controlValue, t);
      }
      if (optionId === `${fieldId}_true`) filledFields?.set(fieldId, 'true');
    }
    return;
  }

  // File upload fields need a real file(s) attached.
  if (field.widget === 'atvFile') {
    const fileInput = page.locator(`#${field.fieldName}`);
    await expect(fileInput).toBeVisible();
    if (shouldFill) {
      // Verify that the field error message is visible.
      const fileError = page.locator(`#${field.fieldName}-error`);
      if (field.required) {
        await test.step(`Assert inline error visible: ${field.fieldName}`, async () => {
          await expect(fileError).toBeVisible();
        });
      }

      // Fill attachment fields in the following repeating order:
      // 1. Upload, 2. Deliver later, 3. Already delivered.
      // The sequence follows the field order in the form and resets for each
      // form run. Simple attachment fields are always uploaded.
      const mode = nextAttachmentMode(field);
      if (mode === 'upload') {
        // Fill the form with required attachments.
        const attachments = await uploadAttachments(page, fileInput, field);
        filledFields?.set(fieldId, attachments.join(', '));
      } else {
        // Tick the attachment checkbox instead of uploading.
        await page.click(`label[for="${field.fieldName}-${mode}"]`);
        await expect(page.locator(`#${field.fieldName}-${mode}`)).toBeChecked();
        filledFields?.set(fieldId, mode);
      }
      // Verify the attachment error is gone.
      await expect(fileError).not.toBeVisible();
    }
    // When verifying, check the field still holds the value
    // we set during the fill pass.
    else if (filledFields?.has(fieldId)) {
      const value = filledFields.get(fieldId)!;
      // Verify the ticked checkbox or the uploaded file names.
      if (value in ATTACHMENT_CHECKBOX_LABELS) {
        await expect(page.locator(`#${field.fieldName}-${value}`)).toBeChecked();
      } else {
        await expect(fileInput).toHaveValue(value);
      }
    }
    return;
  }

  const fieldDOM = page.locator(`#${fieldId}`);

  await expect(fieldDOM).toBeVisible();

  // Throw an error if the field is missing the type class.
  const typeToken = await getFieldTypeToken(page, fieldId);
  if (shouldFill && !typeToken) {
    throw new Error(
      `Field "${fieldId}" has no "hdbt-form--field--<type>" class. ` +
      `Add the type class in the React form app so the test can detect this field.`
    );
  }

  // The HDS dropdown has a different structure than a plain input,
  // so detect it and handle it separately from regular text inputs.
  if (await fieldDOM.locator('[aria-haspopup="listbox"]').count() > 0) {
    await expect(page.locator(`label[for="${fieldId}-main-button"]`)).toContainText(t(fieldTitle));
    if (field.tooltipLabel) {
      await expect(fieldDOM.locator('button[class*="tooltipButton"]')).toBeVisible();
    }

    // Open the dropdown, pick the first option, and confirm no error.
    if (shouldFill) {
      filledFields?.set(fieldId, await selectFirstDropdownOption(page, fieldDOM, fieldId));
    }

  // All regular text and number inputs are handled here.
  } else {
    await expect(page.locator(`label[for="${fieldId}"]`)).toContainText(t(fieldTitle));

    if (field.tooltipLabel) {
      await expect(
        page.locator(`label[for="${fieldId}"] ~ div button[class*="tooltipButton"]`)
      ).toBeVisible();
    }

    // Fill the field only if it is enabled and we are in the fill pass.
    if (shouldFill && !await fieldDOM.isDisabled()) {
      const tag = await fieldDOM.evaluate((el: HTMLElement) => el.tagName.toLowerCase());
      const customInput = resolveFieldInput(fieldInputs, field.fieldPath);
      let value: string;

      // Native HTML select: pick index 1 to skip the placeholder option.
      if (tag === 'select') {
        await page.selectOption(`#${fieldId}`, { index: 1 });
        value = await fieldDOM.inputValue();
      }
      // A custom value was provided for this field.
      else if (customInput !== undefined) {
        value = typeof customInput === 'function' ? customInput() : customInput;
        await page.fill(`#${fieldId}`, value);
        usedFieldInputs?.add(fieldId);
      }
      // Date fields need a Finnish date format, e.g. "30.4.2026".
      else if (typeToken === 'date') {
        const isEndDate = field.fieldName.includes('_end');
        value = finnishDate(isEndDate ? 2 : 1);
        await page.fill(`#${fieldId}`, value);
        // Blur the field to save the date.
        await fieldDOM.blur();
      }
      // Amount fields get a random number with decimals.
      else if (field?.format === 'decimal-number') {
        const decimal = faker.number.int({ min: 10, max: 99 }).toString();
        value = faker.number.int({ min: 1, max: 99999 }).toString();
        await page.fill(`#${fieldId}`, `${value},${decimal}`);
      }
      // Postal codes are five digits and may start with a zero, so they are
      // filled as a string rather than a number.
      else if (field?.format === 'postal-code') {
        value = faker.string.numeric({ length: 5, allowLeadingZeros: true });
        await page.fill(`#${fieldId}`, value);
      }
      // Year fields get a random year.
      else if (field?.format === 'year' || field.fieldName.endsWith('_year')) {
        value = faker.number.int({ min: 1980, max: 2020 }).toString();
        await page.fill(`#${fieldId}`, value);
      }
      // Integer and number fields get a random whole number.
      else if (field.type === 'integer' || field.type === 'number') {
        value = faker.number.int({ min: 1, max: 999 }).toString();
        await page.fill(`#${fieldId}`, value);
      }
      // Everything else gets random filler text.
      else {
        value = faker.lorem.sentences(4);
        // Keep the value within the field's allowed length so the saved
        // value matches what we verify later in the preview.
        if (field.maxLength && value.length > field.maxLength) {
          value = value.slice(0, field.maxLength);
        }
        await page.fill(`#${fieldId}`, value);
      }

      await assertFieldErrorGone(page, fieldId);
      filledFields?.set(fieldId, value);

    // Disabled field, like applied_benefits has a computed value.
    // Capture the computed value so verifying steps can assert it.
    // F.e. applied_benefits updates asynchronously.
    } else if (shouldFill) {
      await expect(fieldDOM).not.toHaveValue(/^0*$/, { timeout: 5000 });
      const value = await fieldDOM.inputValue();
      if (value) filledFields?.set(fieldId, value);
    }

    // When only verifying translations, check that the field still
    // shows the value we entered during the Finnish fill pass.
    else if (
      !shouldFill &&
      filledFields !== undefined &&
      filledFields.has(fieldId)
    ) {
      await expect(fieldDOM).toHaveValue(filledFields.get(fieldId)!);
    }
  }
}

/**
 * Goes through all sections and fields on a single step.
 *
 * @param page
 *   The Playwright page instance.
 * @param language
 *   The language currently being tested.
 * @param step
 *   The step key, e.g. 'grant_info_step'.
 * @param sections
 *   All sections and their fields for this step.
 * @param t
 *   The translation function for the current language.
 * @param shouldFill
 *   Set to true to fill fields, false to only check labels.
 * @param filledFields
 *   Map to store entered values in when shouldFill is true.
 * @param fieldInputs
 *   Custom fill values, keyed by field id.
 * @param usedFieldInputs
 *   Set that collects the field ids whose custom value was applied.
 * @param formLogic
 *   Custom form logic for fields that the generic engine cannot handle.
 */
export async function verifyStep(
  page: Page,
  language: string,
  step: string,
  sections: Record<string, Record<string, StepField>>,
  t: (key: string) => string,
  shouldFill = false,
  filledFields?: FilledFields,
  fieldInputs?: FieldInputs,
  usedFieldInputs?: Set<string>,
  formLogic?: FormLogic,
): Promise<void> {
  const stepFields = Object.values(sections).flatMap((fields) => Object.values(fields));

  for (const [section, fields] of Object.entries(sections)) {
    const sectionFields = Object.values(fields);
    // A section without a title in the schema shows no heading.
    const titleKey = sectionFields.find((field) => field.sectionTitleKey)?.sectionTitleKey;
    const sectionTitle = titleKey ? t(titleKey) : section;
    const hasTitle = !!titleKey && sectionTitle !== titleKey;
    const sectionHeading = page.locator('h3.hdbt-form--section__title').filter({ hasText: sectionTitle }).first();
    const playwrightStepLabel = shouldFill ? 'Filling' : `Verifying (${language}) translations for`;

    // Sections like "Monivuotisten hankkeiden väliraportointi" belong to an answer the test did not choose,
    // so check their fields stay hidden instead of filling them.
    if (sectionFields.every((field) => field.active === false)) {
      if (shouldFill) {
        await test.step(`Checking hidden section: ${hasTitle ? sectionTitle : section}...`, async () => {
          for (const field of sectionFields) {
            await expect(
              fieldInput(page, field, `root_${field.fieldPath.join('_')}`),
              `Form hides "${t(field.titleKey)}" for the chosen answers.`,
            ).toHaveCount(0);
          }
        });
      }
      continue;
    }

    // Skip a conditional section on the translation pass when its answers are not given.
    const conditionalSection = sectionFields.length > 0 && sectionFields.every((field) => field.conditional);
    if (hasTitle && conditionalSection && !shouldFill && (await sectionHeading.count()) === 0) {
      continue;
    }

    await test.step(`${playwrightStepLabel} section: ${hasTitle ? sectionTitle : section}...`, async () => {
      if (hasTitle) {
        await expect(sectionHeading).toBeVisible();
      }
    });

    // Track which array groups have been handled so we don't click "Add" twice.
    const addedArrays = new Set<string>();

    // On the translation pass, give the answers that open other fields,
    // so the labels of those fields can be verified.
    if (!shouldFill) {
      for (const control of sectionFields) {
        if (control.controlValue === undefined || control.isArrayItem) continue;
        const answer = page.locator(`label[for="root_${control.fieldPath.join('_')}_${String(control.controlValue)}"]`);
        if ((await answer.count()) === 0) continue;
        await answer.click();
        await expectConditionalFields(page, stepFields, control.controlPaths ?? [], control.controlValue, t);
      }
    }

    // Go through each field in current section one by one.
    for (const [, field] of Object.entries(fields)) {
      const fieldId = `root_${field.fieldPath.join('_')}`;
      const fieldTitle = field.titleKey;

      // Fields of an answer the test did not choose must stay hidden.
      if (field.active === false) {
        if (shouldFill) {
          await expect(fieldInput(page, field, fieldId), `Form hides "${t(fieldTitle)}" for the chosen answers.`).toHaveCount(0);
        }
        continue;
      }

      logger(`${shouldFill ? 'Fill' : `Check ${language}`}: ${section} / ${field.fieldName}`);
      await test.step(`${playwrightStepLabel} field: ${t(fieldTitle)}`, () =>
        handleField(
          page, field, fieldId, fieldTitle,
          t, shouldFill,
          stepFields, addedArrays, filledFields,
          fieldInputs, usedFieldInputs, formLogic,
        )
      );
    }
  }
}

/**
 * Get the translation key of a step title.
 *
 * @param formData
 *   The form schema and translations fetched from the server.
 * @param step
 *   The step name.
 *
 * @return
 *   The step title key from the schema.
 */
function stepTitleKey(formData: Pick<FormPreviewResponse, 'schema'>, step: string): string {
  return (formData.schema.properties[step] as { title?: string } | undefined)?.title ?? `${step}.title`;
}

/**
 * Opens the form in each language and checks that every field label,
 * tooltip and description shows the correct translated text.
 * No fields are filled during this step.
 *
 * @param page
 *   The Playwright page instance.
 * @param formData
 *   The form schema and translations fetched from the server.
 * @param options
 *   Languages to test and the form URL.
 */
export async function verifyFormFieldTranslations(
  page: Page,
  formData: Pick<FormPreviewResponse, 'schema' | 'ui_schema' | 'translations'>,
  options: VerifyFormFieldsOptions = {},
): Promise<undefined> {
  const languages = options.languages ?? ['fi', 'sv', 'en'];
  const tree = buildFormTree(formData as any, 'en', collectFormAnswers(options.formLogic));

  // Loop through each language and open the form in that language.
  for (const [languageIndex, language] of languages.entries()) {
    logger(`Verifying translations for language: ${language}`);
    await test.step(`Verifying form for language: ${language}`, async () => {
      const t = createTranslator(formData as FormPreviewResponse, language);

      if (languageIndex > 0) {
        await page.click(`a.language-link[lang="${language}"]`);
        await page.waitForURL(`**/${language}/**`);
        await waitForForm(page);
      }

      // Go through each step in the form and verify its labels.
      for (const [stepIndex, [step, sections]] of Object.entries(tree).entries()) {
        const titleKey = stepTitleKey(formData, step);
        const stepTitle = t(titleKey);
        logger(`Step: ${stepTitle}`);

        await test.step(`Verifying translations for step: ${stepTitle}...`, async () => {
          if (!stepTitle) throw new Error(`No translation found for ${titleKey}`);
          await expect(page.locator('button[aria-current="step"] p')).toContainText(stepTitle);
        });

        // The applicant info step has its own verification logic.
        if (step === 'applicant_info') {
          await verifyApplicantInfoStepFieldTranslations(page, formData, language);
          await clickOnStep(page, stepIndex + 1);
          continue;
        }

        // On the final step just confirm the terms checkbox is visible,
        // then go back to the first step for the next language.
        if (step === 'confirm_and_submit') {
          await expect(page.locator('#final-acceptance')).toBeVisible();
          if (languageIndex < languages.length - 1) {
            await clickOnStep(page, 0);
          }
          continue;
        }

        // For all other steps, check field labels without filling anything.
        await verifyStep(page, language, step, sections, t, false, undefined, undefined, undefined, options.formLogic);
        await clickOnStep(page, stepIndex + 1);
      }
    });
  }
}

/**
 * Navigates to the final step and checks that every filled value
 * appears correctly in the summary view.
 *
 * @param page
 *   The Playwright page instance.
 * @param tree
 *   The form structure built from the schema.
 * @param t
 *   The translation function for the current language.
 * @param filledFields
 *   The values that were filled in during the Finnish fill pass.
 * @param language
 *   The language of the preview.
 */
async function verifyPreviewStep(
  page: Page,
  tree: FormTree,
  t: (key: string) => string,
  filledFields: FilledFields,
  language: string,
): Promise<void> {

  const step = 'confirm_and_submit';
  const confirmStep = tree[step] ?? false;
  if (!confirmStep) throw new Error(`Cannot find the schema definitions for ${step} step.`);

  await clickOnStepWithTitle(page, t, `${step}.title`);
  await expect(page.locator('h2.grants-form__page-title')).toContainText(t(`${step}.title`));

  await verifyPreviewValues(page, tree, t, filledFields, language);
}

/**
 * Check that every filled value appears in the preview rendered on the page.
 *
 * @param page
 *   The Playwright page instance.
 * @param tree
 *   The form structure built from the schema.
 * @param t
 *   The translation function for the current language.
 * @param filledFields
 *   The values that were filled in during the Finnish fill pass.
 * @param language
 *   The language of the preview. Defaults to 'fi'.
 */
async function verifyPreviewValues(
  page: Page,
  tree: FormTree,
  t: (key: string) => string,
  filledFields: FilledFields,
  language = 'fi',
): Promise<void> {
  const preview = page.locator('.hdbt-form__preview');
  await expect(preview).toBeVisible();

  // Match the whole title text.
  const exactText = (text: string) => new RegExp(`^\\s*${text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*$`);

  // Loop through all fields and check each value appears in the preview.
  for (const [, [, sections]] of Object.entries(tree).entries()) {
    for (const [, [, fields]] of Object.entries(sections).entries()) {
      for (const [, field] of Object.entries(fields)) {
        const fieldId = `root_${field.fieldPath.join('_')}`;
        const filledValue = filledFields.get(fieldId);
        const fieldTitle = t(field.titleKey);

        // Skip if there is no value or the value is a radio button value.
        if (!filledValue || filledValue === 'true' || filledValue === 'false') continue;

        // Dropdowns like "Tilan omistava toimiala" show a translated value,
        // so check them in the Finnish preview only.
        if (field.enumValues && language !== 'fi') continue;

        // Translate the attachment checkbox text shown in place of a file name.
        const checkboxLabel = ATTACHMENT_CHECKBOX_LABELS[filledValue];
        const value = checkboxLabel
          ? await page.evaluate((source) => (window as any).Drupal.t(source, {}, { context: 'grants_attachments' }), checkboxLabel)
          : filledValue;

        await test.step(`Preview: ${fieldTitle} = ${value}`, async () => {

          // Narrow the search area to this section so we don't accidentally
          // find the value in a different section with the same field name.
          const sectionContainer = preview
            .locator('section.grants-form--preview-section')
            .filter({has: page.locator('h4.hdbt-form--section__title', {hasText: exactText(field.sectionTitleKey ? t(field.sectionTitleKey) : '')})});
          const scope = (field.sectionTitleKey && await sectionContainer.count() > 0) ? sectionContainer : preview;

          // First try: find the field by its label span and check the
          // value appears next to it.
          const bySpanLabel = scope
            .locator('span.grants-form--preview-section__label')
            .filter({hasText: exactText(fieldTitle)})
            .locator('xpath=..');

          if (await bySpanLabel.count() > 0) {
            if (fieldId.includes('issuer')) {
              // @todo test the issuer -dropdown field's selected value properly:
              // The selected value is now translated on preview page and the test should be
              // changed to expect the translated value instead of always expecting a
              // the finnish value - f.ex. Valtio - State
              await expect(bySpanLabel.first()).not.toContainText('-', { timeout: 5000 });
            } else {
              // Accept any field with the same label.
              await expect(bySpanLabel.filter({hasText: value}).first(), `Preview shows "${value}" for ${fieldTitle}.`).toBeVisible();
              return;
            }
          }

          // Second try: find the value in the field's section.
          if (scope !== preview && await scope.filter({ hasText: value }).count() > 0) return;

          // Third try: some fields have their own section heading.
          // Check the value appears inside that section.
          const byFieldTitle = preview
            .locator('section.grants-form--preview-section')
            .filter({has: page.locator('h4.hdbt-form--section__title', {hasText: exactText(fieldTitle)})});
          if (await byFieldTitle.count() > 0) {
            await expect(byFieldTitle.locator('.hdbt-form--section__content')).toContainText(value);
          }
        });
      }
    }
  }
}

/**
 * Open the submitted application from "oma-asiointi" and verify its values.
 *
 * The "sent" application renders the same preview, so this confirms the stored
 * values still match the inputs after the round trip through the backend.
 *
 * @param page
 *   The Playwright page instance.
 * @param formData
 *   The form schema and translations fetched from the server.
 * @param applicationNumber
 *   The submitted application number.
 * @param filledFields
 *   The values that were filled in during the Finnish fill pass.
 */
export async function verifySentApplication(
  page: Page,
  formData: Pick<FormPreviewResponse, 'schema' | 'ui_schema' | 'translations'>,
  applicationNumber: string,
  filledFields: FilledFields,
): Promise<void> {
  const tree = buildFormTree(formData as any);
  const t = createTranslator(formData as FormPreviewResponse, 'fi');

  await openSentApplication(page, applicationNumber);
  await verifyPreviewValues(page, tree, t, filledFields);
}

/**
 * Open a submitted application from the "sent" list on oma-asiointi.
 *
 * @param page
 *   The Playwright page instance.
 * @param applicationNumber
 *   The submitted application number.
 */
async function openSentApplication(page: Page, applicationNumber: string): Promise<void> {
  await page.goto('/fi/oma-asiointi');
  await page.waitForURL('**/oma-asiointi');
  const row = page.locator('#oma-asiointi__sent .application-list__item', { hasText: applicationNumber });
  await row.locator('.application-list__item__link a').first().click();
}

/**
 * Open a submitted application, edit a field, re-submit and verify the changes.
 *
 * @param page
 *   The Playwright page instance.
 * @param formData
 *   The form schema and translations fetched from the server.
 * @param applicationNumber
 *   The submitted application number.
 * @param filledFields
 *   The values entered during the fill pass, updated with the new value.
 */
export async function modifySubmittedApplication(
  page: Page,
  formData: Pick<FormPreviewResponse, 'schema' | 'ui_schema' | 'translations'>,
  applicationNumber: string,
  filledFields: FilledFields,
): Promise<void> {
  const tree = buildFormTree(formData as any);
  const t = createTranslator(formData as FormPreviewResponse, 'fi');

  // Open the application and follow the edit link.
  await openSentApplication(page, applicationNumber);
  await page.locator('.hdbt-react-form__submission-info__row--edit a').first().click();
  await waitForFormLoad(page);

  // Find a filled textarea so we can change its value.
  const target = findEditableTextField(tree, filledFields);
  if (!target) throw new Error('No editable text field found to modify.');

  // Change the value on its step.
  await clickOnStepWithTitle(page, t, stepTitleKey(formData, target.step));
  let value = faker.lorem.sentences(2);
  if (target.maxLength && value.length > target.maxLength) {
    value = value.slice(0, target.maxLength);
  }
  await page.fill(`#${target.fieldId}`, value);
  filledFields.set(target.fieldId, value);

  // Resubmit and confirm the new value is stored.
  await submitFromConfirmStep(page, formData, `/fi/application/${applicationNumber}/completion`);
  await openSentApplication(page, applicationNumber);
  await verifyPreviewValues(page, tree, t, filledFields);
}

/**
 * Find the first non-conditional textarea that has a filled value.
 *
 * @param tree
 *   The form structure built from the schema.
 * @param filledFields
 *   The values entered during the fill pass.
 */
function findEditableTextField(
  tree: FormTree,
  filledFields: FilledFields,
): { fieldId: string; step: string; maxLength?: number } | null {
  for (const [step, sections] of Object.entries(tree)) {
    for (const fields of Object.values(sections)) {
      for (const field of Object.values(fields)) {
        const fieldId = `root_${field.fieldPath.join('_')}`;
        if (!filledFields.has(fieldId)) continue;
        // A textarea always accepts free text, unlike selects or formatted inputs.
        if (field.conditional || field.widget !== 'textarea') continue;
        return { fieldId, step, maxLength: field.maxLength };
      }
    }
  }
  return null;
}

/**
 * Builds field IDs from every string or function value found in a nested
 * fieldInputs object.
 *
 * For example:
 * "root_information_in_more_detail_step_grant_target_section_safety_practices".
 *
 * @param node
 *   The nested custom values (or a subtree during recursion).
 * @param prefix
 *   The path segments accumulated so far.
 */
function collectFieldInputIds(node: FieldInputs, prefix: string[] = []): string[] {
  const ids: string[] = [];
  for (const [key, value] of Object.entries(node)) {
    const path = [...prefix, key];
    if (typeof value === 'string' || typeof value === 'function') {
      ids.push(`root_${path.join('_')}`);
    } else if (value && typeof value === 'object') {
      ids.push(...collectFieldInputIds(value, path));
    }
  }
  return ids;
}

/**
 * Validates that all provided field inputs were used.
 *
 * @param fieldInputs
 *   The custom values supplied for this form.
 * @param used
 *   The field ids whose custom value was applied during the fill pass.
 */
function assertAllFieldInputsUsed(fieldInputs: FieldInputs | undefined, used: Set<string>): void {
  if (!fieldInputs) return;
  const unused = collectFieldInputIds(fieldInputs).filter((id) => !used.has(id));
  if (unused.length > 0) {
    throw new Error(
      `Unused field inputs in formInputs.ts: ${unused.join(', ')}. ` +
      `Check for invalid paths or fields that were not reached during form filling.`
    );
  }
}

/**
 * Goes through every step and fills all fields with valid values.
 *
 * Only fills in Finnish. Also verifies field labels as it goes.
 * Saves the form as a draft at the end and returns the filled values
 * so they can be checked later.
 *
 * @param page
 *   The Playwright page instance.
 * @param formData
 *   The form schema and translations fetched from the server.
 * @param options
 *   The form URL, languages, and an optional map to store filled values.
 */
export async function fillFormFields(
  page: Page,
  formData: Pick<FormPreviewResponse, 'schema' | 'ui_schema' | 'translations'>,
  options: VerifyFormFieldsOptions = {},
): Promise<FilledFields> {
  const languages = options.languages ?? ['fi', 'sv', 'en'];
  const tree = buildFormTree(formData as any, 'en', collectFormAnswers(options.formLogic));
  const filledFields:FilledFields = options.filledFields ?? new Map();
  const usedFieldInputs = new Set<string>();
  resetAttachments();

  // Submit the empty form first to trigger all required field errors.
  // This lets us verify that every required field shows an error message.
  await test.step('Gather form errors', async () => {
    if (!options.formURL) throw new Error(`The form URL is missing.`);
    await page.goto(options.formURL);
    await gatherRequiredFieldWarnings(page);
    await assertMissingInputsVisible(page);
  });

  for (const [languageIndex, language] of languages.entries()) {
    const fill = languageIndex === 0;
    const t = createTranslator(formData as FormPreviewResponse, language);

    for (const [, [step, sections]] of Object.entries(tree).entries()) {
      const titleKey = stepTitleKey(formData, step);
      const stepTitle = t(titleKey);
      logger(`Filling step: ${stepTitle}`);
      await test.step(`Checking step: ${stepTitle}...`, async () => {
        if (!stepTitle) throw new Error(`No translation found for ${titleKey}`);
        await expect(page.locator('button[aria-current="step"] p')).toContainText(stepTitle);
      });

      // The applicant info step has its own fill logic.
      if (step === 'applicant_info') {
        await fillApplicantInfoStep(page, formData, language, fill, filledFields);
        await clickNext(page);
        continue;
      }

      // When we reach the final step during the Finnish fill pass,
      // save as draft and go back to the first step for the next language.
      if (step === 'confirm_and_submit') {
        // Fill in the form fields and save the form as draft.
        // Return to the first step of the form.
        if (fill && options.formURL) {
          await assertMissingInputsGone(page);
          logger('Saving the draft...');
          await saveDraft(page, t);
          await page.waitForURL('**/oma-asiointi/hakemukset', { timeout: 30_000 });
          logger('Draft saved. Reopening the form...');
          await page.goto(options.formURL);
          // Expect the React application to load.
          await waitForFormLoad(page);
          await clickOnStep(page, 0);
        }
        // The fill pass is done, so every custom input should have been used.
        if (fill) assertAllFieldInputsUsed(options.fieldInputs, usedFieldInputs);
        return;
      }

      // Fill or verify this step's fields depending on the current pass.
      await verifyStep(page, language, step, sections, t, fill, filledFields, options.fieldInputs, usedFieldInputs, options.formLogic);
      await clickNext(page);
    }
  }
  return filledFields;
}

/**
 * Opens the saved form and checks that all filled values appear
 * correctly in the preview, in every language.
 *
 * @param page
 *   The Playwright page instance.
 * @param formData
 *   The form schema and translations fetched from the server.
 * @param options
 *   Languages to check, form URL, and the filled values to verify.
 */
export async function verifyAnswers(
  page: Page,
  formData: Pick<FormPreviewResponse, 'schema' | 'ui_schema' | 'translations'>,
  options: VerifyFormFieldsOptions = {},
): Promise<FilledFields> {
  const languages = options.languages ?? ['fi', 'sv', 'en'];
  const tree = buildFormTree(formData as any);
  const filledFields:FilledFields = options.filledFields ?? new Map();

  // Due to problems with translating responses we should first just check that
  // the form has correct translated field labels, descriptions and tooltips.
  // For example "Valtio" in English version will be "Valtio".
  // @todo Fix the issue of injecting translated ATV responses to select fields,
  // check the previous todo comment at line 769.
  for (const [languageIndex, language] of languages.entries()) {
    const t = createTranslator(formData as FormPreviewResponse, language);
    logger(`Verifying the preview answers in: ${language}`);

    // Switch the language and wait for the form to load.
    if (languageIndex > 0) {
      await page.click(`a.language-link[lang="${language}"]`);
      await page.waitForURL(`**/${language}/**`);
      await waitForForm(page);
    }

    // Go to last step and check that the values are correct.
    await verifyPreviewStep(page, tree, t, filledFields, language);
  }
}

/**
 * Verify the form and submit it.
 *
 * Opens the form on the final step, accepts the terms, submits it,
 * and checks that it was received successfully.
 *
 * @param page
 *   The Playwright page instance.
 * @param formData
 *   The form schema and translations fetched from the server.
 * @param options
 *   The form URL and the URL shown after a successful submission.
 */
export async function verifyFormAndSubmit(
  page: Page,
  formData: Pick<FormPreviewResponse, 'schema' | 'ui_schema' | 'translations'>,
  options: VerifyFormFieldsOptions = {},
): Promise<boolean> {
  let applicationReceived = false;

  // Open the form, navigate to the final step, accept the terms,
  // submit, and verify the confirmation page is shown.
  await test.step('Submit the form', async () => {
    if (!options.formCompletionURL) throw new Error(`The form completion URL is missing.`);
    if (!options.formURL) throw new Error(`The form URL is missing.`);
    logger('Opening the form for submit...');
    await page.goto(options.formURL);
    // Expect the React application to load.
    await waitForFormLoad(page);
    applicationReceived = await submitFromConfirmStep(page, formData, options.formCompletionURL);
  });
  return applicationReceived;
}

/**
 * Go to the final step, accept the terms, submit, and wait for completion.
 *
 * Return whether the received confirmation was shown.
 *
 * @param page
 *   The Playwright page instance.
 * @param formData
 *   The form schema and translations fetched from the server.
 * @param formCompletionURL
 *   The URL shown after a successful submission.
 */
async function submitFromConfirmStep(
  page: Page,
  formData: Pick<FormPreviewResponse, 'schema' | 'ui_schema' | 'translations'>,
  formCompletionURL: string,
): Promise<boolean> {
  // Form submissions are only tested in Finnish.
  const language = 'fi';
  const t = createTranslator(formData as FormPreviewResponse, language);
  const step = 'confirm_and_submit';

  await clickOnStepWithTitle(page, t, `${step}.title`);
  await expect(page.locator('h2.grants-form__page-title')).toContainText(t(`${step}.title`));

  const preview = page.locator('.hdbt-form__preview');
  await expect(preview).toBeVisible();

  const submitButton = page.locator('.hdbt-form--actions').getByRole('button', { name: t('submit') });
  await expect(submitButton).toBeVisible();

  const agreeTermsCheckbox = page.locator('label[for="final-acceptance"]');
  await expect(agreeTermsCheckbox).toBeVisible();
  await agreeTermsCheckbox.click();

  logger('Attempting to submit the form...')
  await expect(submitButton).not.toHaveAttribute('disabled');
  const [submitResponse] = await Promise.all([
    page.waitForResponse(response =>
      /\/applications\/[^/]+\/application\//.test(response.url()) && ['POST', 'PATCH'].includes(response.request().method())
    ),
    submitButton.click(),
  ]);

  // Fail fast if the form submit returns a server error.
  expect(submitResponse.status(), 'Application submit returned a server error.').toBeLessThan(500);

  // Verify the completion.
  await logCurrentUrl(page);
  await page.waitForURL(formCompletionURL);
  await expect(page.getByRole('heading', {name: COMPLETION_TEXT.heading[language]})).toBeVisible();
  await expect(page.getByText(COMPLETION_TEXT.sent[language]).first()).toBeVisible();

  // Attempt to locate the "Vastaanotettu" text on the page. Keep polling for 60000ms (1 minute).
  // Note: We do this instead of using Playwrights "expect" method so that test execution isn't interrupted if this fails.
  const applicationReceived = await waitForTextWithInterval(page, COMPLETION_TEXT.received[language]);
  if (!applicationReceived) {
    logger('WARNING: Failed to validate that the application was received.');
  }
  return applicationReceived;
}

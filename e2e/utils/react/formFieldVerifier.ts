import path from 'path';
import { expect, type Locator, type Page, test } from '@playwright/test';
import { fakerFI as faker } from "@faker-js/faker/locale/index";
import { buildFormTree, type FormTree, type StepField } from './stepInspector';
import type { FormPreviewResponse } from './schemaFetcher';
import {
  fillApplicantInfoStep,
  verifyApplicantInfoStepFieldTranslations
} from './applicantInfoStep';
import {
  assertFieldErrorGone,
  assertMissingInputsGone,
  assertMissingInputsVisible,
  clickNext,
  clickOnStep,
  clickOnStepWithTitle,
  createTranslator,
  gatherRequiredFieldWarnings,
  saveDraft,
  waitForForm,
  waitForFormLoad,
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
 * Custom form logic for fields that the generic engine cannot handle.
 *
 * The structure is nested by field path, like FieldInputs. Handler functions
 * are placed at "leaf" nodes. Use this only for true one-off widgets or
 * interactions; recurring patterns belong in the engine.
 */
export type FormLogic = { [key: string]: FieldLogicHandler | FormLogic };

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
 * @param step
 * @param section
 * @param t
 * @param shouldFill
 * @param triggeredConditions
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
  step: string,
  section: string,
  t: (key: string) => string,
  shouldFill: boolean,
  triggeredConditions: Set<string>,
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

  // Some conditional fields are revealed by a boolean toggle.
  // Activate the condition once before interacting with the field.
  if (field.conditional && field.conditionField && !triggeredConditions.has(field.conditionField)) {
    const toggle = page.locator(`label[for="root_${step}_${section}_${field.conditionField}_true"]`);
    if (await toggle.count() > 0) {
      await toggle.click();
    }
    triggeredConditions.add(field.conditionField);
  }

  // The current field belongs to a repeatable list, like "Applied grants".
  if (field.isArrayItem && field.arrayField && !addedArrays.has(field.arrayField)) {
    const addText = field.addButtonTextKey ? t(field.addButtonTextKey) : undefined;
    const fieldElement = page.locator(`#${fieldId}`);

    // Get the current scope by searching the ".field-array" element.
    const arrayContainer = await fieldElement.count() > 0
      ? fieldElement.locator('xpath=ancestor::div[contains(@class,"field-array")][1]')
      : page.locator('.field-array').filter({
          has: page.getByRole('button', { name: addText ?? /Add|Lisää|Lägg till/i }),
        }).first();

    // Remove auto-added empty rows left over from the error-gathering pass.
    const errorItems = arrayContainer.locator('.array-item').filter({ has: page.locator('.has-error') });
    let errorCount = await errorItems.count();
    while (errorCount > 0) {
      logger('Found empty list item, removing it.');
      await errorItems.first().getByRole('button', { name: /Remove|Poista|Ta bort/i }).click();
      await expect(errorItems).toHaveCount(errorCount - 1);
      errorCount = await errorItems.count();
    }

    // Add a row only when one is not already present.
    if (await fieldElement.count() === 0) {
      await page.getByRole('button', { name: addText ?? /Add|Lisää|Lägg till/i }).first().click();
    }
    addedArrays.add(field.arrayField);
    if (field.groupDescriptionKey) {
      await expect(
        page.locator('.hdbt-form--description').filter({ hasText: t(field.groupDescriptionKey) }).first()
      ).toBeVisible();
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

  // When handling a radio-button, pick the "truthy" option when there is one,
  // otherwise pick the first option.
  if (field.widget === 'radio') {
    const trueOption = page.locator(`#${fieldId}_true`);
    const option = (await trueOption.count()) > 0
      ? trueOption
      : page.locator(`input[type="radio"][id^="${fieldId}_"]`).first();
    await expect(option).toBeVisible();
    const optionId = (await option.getAttribute('id')) ?? '';
    await expect(page.locator(`fieldset:has(#${optionId}) legend`)).toContainText(t(fieldTitle));
    if (shouldFill) {
      await page.click(`label[for="${optionId}"]`);
      await assertFieldErrorGone(page, fieldId);
      if (optionId === `${fieldId}_true`) filledFields?.set(fieldId, 'true');
    }
    return;
  }

  // File upload fields need a real file(s) attached.
  if (field.widget === 'atvFile') {
    const fileInput = page.locator(`#${field.fieldName}`);
    await expect(fileInput).toBeVisible();
    // Fill the form with two files.
    if (shouldFill) {
      const attachments = ['07_muu_liite.pdf', '08_muu_liite.pdf'];
      for (const attachment of attachments) {
        // Register before setInputFiles so we don't miss the response event.
        const uploadDone = page.waitForResponse(
          r => r.url().includes('/upload') && r.ok(),
          { timeout: 15000 },
        );
        await fileInput.setInputFiles(path.join(__dirname, '../data/attachments', attachment));
        // Each upload must be completed before the next upload, otherwise only
        // one file is actually uploaded.
        await uploadDone;
        await expect(page.locator('.hdbt-form--fileinput').filter({ hasText: attachment })).toBeVisible();
      }
      filledFields?.set(fieldId, attachments.join(', '));
    }
    // When verifying, check the description still holds the value
    // we typed during the fill pass.
    else if (filledFields?.has(fieldId)) {
      await expect(fileInput).toHaveValue(filledFields!.get(fieldId)!);
    }
    return;
  }

  const fieldDOM = page.locator(`#${fieldId}`);

  // Conditional fields are skipped when their condition is not active.
  if (field.conditional) {
    if (!shouldFill) {
      if ((await fieldDOM.count()) === 0) return;
    } else {
      // Filling may reveal the field after a short React debounce.
      try {
        await fieldDOM.waitFor({ state: 'visible', timeout: 3000 });
      } catch {
        return;
      }
    }
  }

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
      }
      // Amount fields get a random number with decimals.
      else if (field?.format === 'decimal-number') {
        const decimal = faker.number.int({ min: 10, max: 99 }).toString();
        value = faker.number.int({ min: 1, max: 99999 }).toString();
        await page.fill(`#${fieldId}`, `${value},${decimal}`);
      }
      // Year fields get a random year.
      else if (field.fieldName.endsWith('_year')) {
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
  for (const [section, fields] of Object.entries(sections)) {
    const titleKey = `${section}.title`;
    const sectionTitle = t(titleKey);
    // A section can be without a title.
    const hasTitle = sectionTitle !== titleKey;
    const sectionHeading = page.locator('h3.hdbt-form--section__title').filter({ hasText: sectionTitle }).first();

    // Skip a conditional section when it is not on the page.
    const sectionFields = Object.values(fields);
    const conditionalSection = sectionFields.length > 0 && sectionFields.every((field) => field.conditional);
    if (hasTitle && conditionalSection) {
      if (shouldFill) {
        try {
          await sectionHeading.waitFor({ state: 'visible', timeout: 3000 });
        } catch {
          continue;
        }
      } else if ((await sectionHeading.count()) === 0) {
        continue;
      }
    }

    const playwrightStepLabel = shouldFill ? 'Filling' : `Verifying (${language}) translations for`;
    await test.step(`${playwrightStepLabel} section: ${hasTitle ? sectionTitle : section}...`, async () => {
      if (hasTitle) {
        await expect(sectionHeading).toBeVisible();
      }
    });

    // Track which conditional toggles and array groups have been handled
    // so we don't click "Add" or trigger the same condition twice.
    const triggeredConditions = new Set<string>();
    const addedArrays = new Set<string>();

    // On the translation pass, set the yes option on affirmative radios so
    // their revealed fields render and their labels can be verified.
    if (!shouldFill) {
      for (const expander of Object.values(fields)) {
        if (!expander.affirmativeExpands) continue;
        const expanderId = `root_${expander.fieldPath.join('_')}`;
        const yesOption = page.locator(`label[for="${expanderId}_true"]`);
        if ((await yesOption.count()) === 0) continue;
        await yesOption.click();
        triggeredConditions.add(expander.fieldName);
        const dependent = Object.values(fields).find((f) => f.conditionField === expander.fieldName);
        if (dependent) {
          const dependentId = `root_${dependent.fieldPath.join('_')}`;
          await page.locator(`#${dependentId}`).waitFor({ state: 'visible', timeout: 3000 }).catch(() => undefined);
        }
      }
    }

    // Go through each field in current section one by one.
    for (const [, field] of Object.entries(fields)) {
      const fieldId = `root_${field.fieldPath.join('_')}`;
      const fieldTitle = field.titleKey;

      logger(`${shouldFill ? 'Fill' : `Check ${language}`}: ${section} / ${field.fieldName}`);
      await test.step(`${playwrightStepLabel} field: ${t(fieldTitle)}`, () =>
        handleField(
          page, field, fieldId, fieldTitle,
          step, section, t, shouldFill,
          triggeredConditions, addedArrays, filledFields,
          fieldInputs, usedFieldInputs, formLogic,
        )
      );
    }
  }
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
  const tree = buildFormTree(formData as any);

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
        const stepTitle = t(`${step}.title`);
        logger(`Step: ${stepTitle}`);

        await test.step(`Verifying translations for step: ${stepTitle}...`, async () => {
          if (!stepTitle) throw new Error(`No translation found for ${step}.title`);
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
 */
async function verifyPreviewStep(
  page: Page,
  tree: FormTree,
  t: (key: string) => string,
  filledFields: FilledFields,
): Promise<void> {

  const step = 'confirm_and_submit';
  const confirmStep = tree[step] ?? false;
  if (!confirmStep) throw new Error(`Cannot find the schema definitions for ${step} step.`);

  await clickOnStepWithTitle(page, t, `${step}.title`);
  await expect(page.locator('h2.grants-form__page-title')).toContainText(t(`${step}.title`));

  await verifyPreviewValues(page, tree, t, filledFields);
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
 */
async function verifyPreviewValues(
  page: Page,
  tree: FormTree,
  t: (key: string) => string,
  filledFields: FilledFields,
): Promise<void> {
  const preview = page.locator('.hdbt-form__preview');
  await expect(preview).toBeVisible();

  // Loop through all fields and check each value appears in the preview.
  for (const [, [, sections]] of Object.entries(tree).entries()) {
    for (const [, [section, fields]] of Object.entries(sections).entries()) {
      for (const [, field] of Object.entries(fields)) {
        const fieldId = `root_${field.fieldPath.join('_')}`;
        const value = filledFields.get(fieldId);
        const fieldTitle = t(field.titleKey);
        const sectionTitle = t(`${section}.title`);

        // Skip if there is no value or the value is a radio button value.
        if (!value || value === 'true' || value === 'false') continue;

        // Build an exact-match pattern for the field title to avoid a short
        // title like "Vuosi" matching a longer label like "Vuosi, jolle haen".
        const exactFieldTitle = new RegExp(`^\\s*${fieldTitle.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*$`);

        await test.step(`Preview: ${fieldTitle} = ${value}`, async () => {

          // Narrow the search area to this section so we don't accidentally
          // find the value in a different section with the same field name.
          const sectionContainer = preview
            .locator('section.grants-form--preview-section')
            .filter({has: page.locator('h4.hdbt-form--section__title', {hasText: sectionTitle})});
          const scope = (await sectionContainer.count() > 0) ? sectionContainer : preview;

          // First try: some fields have their own section heading.
          // Check the value appears inside that section.
          const byFieldTitle = preview
            .locator('section.grants-form--preview-section')
            .filter({has: page.locator('h4.hdbt-form--section__title', {hasText: fieldTitle})});
          if (await byFieldTitle.count() > 0) {
            await expect(byFieldTitle.locator('.hdbt-form--section__content')).toContainText(value);
            return;
          }

          // Second try: find the field by its label span and check the
          // value appears next to it.
          const bySpanLabel = scope
            .locator('span.grants-form--preview-section__label')
            .filter({hasText: exactFieldTitle})
            .locator('xpath=..')
            .first();

          if (await bySpanLabel.count() > 0) {
            if (fieldId.includes('issuer')) {
              // TODO test the issuer -dropdown field's selected value properly:
              // The selected value is now translated on preview page and the test should be
              // changed to expect the translated value instead of always expecting a
              // the finnish value - f.ex. Valtio - State
              await expect(bySpanLabel).not.toContainText('-', { timeout: 5000 });
            } else {
              await expect(bySpanLabel).toContainText(value);
              return;
            }
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
  await clickOnStepWithTitle(page, t, `${target.step}.title`);
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
  const tree = buildFormTree(formData as any);
  const filledFields:FilledFields = options.filledFields ?? new Map();
  const usedFieldInputs = new Set<string>();

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
      const stepTitle = t(`${step}.title`);
      logger(`Filling step: ${stepTitle}`);
      await test.step(`Checking step: ${stepTitle}...`, async () => {
        if (!stepTitle) throw new Error(`No translation found for ${step}.title`);
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
          await saveDraft(page, t);
          await page.waitForURL('**/oma-asiointi/hakemukset', { timeout: 30_000 });
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

  for (const [languageIndex, language] of languages.entries()) {
    const t = createTranslator(formData as FormPreviewResponse, language);

    // Switch the language and wait for the form to load.
    if (languageIndex > 0) {
      await page.click(`a.language-link[lang="${language}"]`);
      await page.waitForURL(`**/${language}/**`);
      await waitForForm(page);
    }

    // Go to last step and check that the values are correct.
    await verifyPreviewStep(page, tree, t, filledFields);
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
  await submitButton.click();

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

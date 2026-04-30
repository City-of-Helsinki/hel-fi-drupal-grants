import path from 'path';
import { expect, type Page, test } from '@playwright/test';
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
};

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
): Promise<void> {
  // Some fields are hidden behind a yes/no toggle. Click "yes" once
  // to make those fields appear before we try to interact with them.
  if (field.conditional && field.conditionField && !triggeredConditions.has(field.conditionField)) {
    await page.click(`label[for="root_${step}_${section}_${field.conditionField}_true"]`);
    triggeredConditions.add(field.conditionField);
  }

  // This field belongs to a repeatable list, like "Applied grants".
  if (field.isArrayItem && field.arrayField && !addedArrays.has(field.arrayField)) {
    // Find and remove any empty list item that was auto-added by the
    // form, since it would cause errors if left unfilled.
    const emptyItem = page.locator('.array-item').filter({ has: page.locator('.has-error') }).first();
    if (await emptyItem.count() > 0) {
      await emptyItem.getByRole('button', { name: /Remove|Poista|Ta bort/i }).click();
    }

    // Click "Add" to create a new list item to fill in.
    const addText = field.addButtonTextKey ? t(field.addButtonTextKey) : undefined;
    await page.getByRole('button', { name: addText ?? /Add|Lisää|Lägg till/i }).first().click();
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
    for (const option of field.options) {
      const optionId = `${fieldId}-${option.id}`;
      await expect(page.locator(`#${optionId}`)).toBeVisible();
      await expect(page.locator(`label[for="${optionId}"]`)).toBeVisible();

      // Fill each amount input with a random number.
      if (shouldFill) {
        const fieldValue = faker.number.int({ min: 1, max: 99999 }).toString();
        const decimal = faker.number.int({ min: 10, max: 99 }).toString();
        await page.fill(`#${optionId}`, `${fieldValue},${decimal}`);
        filledFields?.set(optionId, fieldValue);
      }
    }
    return;
  }

  // Handle the radio buttons by selecting "Yes", to expand the extra
  // questions.
  if (field.widget === 'radio') {
    await expect(page.locator(`#${fieldId}_true`)).toBeVisible();
    await expect(page.locator(`#${fieldId}_false`)).toBeVisible();
    await expect(page.locator(`fieldset:has(#${fieldId}_true) legend`)).toContainText(t(fieldTitle));
    if (shouldFill) {
      await page.click(`label[for="${fieldId}_true"]`);
      await assertFieldErrorGone(page, fieldId);
      filledFields?.set(fieldId, 'true');
    }
    return;
  }

  // File upload fields need a real file attached and a description
  // filled in.
  // @todo Test with two or more files.
  if (field.widget === 'atvFile') {
    const descriptionLocator = page.locator(`#${field.fieldName}-description`);
    await expect(descriptionLocator).toBeVisible();
    if (shouldFill) {
      // The actual file input is hidden inside the upload component.
      // Walk up the DOM to find it.
      const fileInput = descriptionLocator.locator(
        'xpath=ancestor::div[contains(@class,"hdbt-form--fileinput")][1]//input[@type="file"]'
      );
      await fileInput.setInputFiles(path.join(__dirname, '../data/attachments/07_muu_liite.pdf'));
      const description = faker.lorem.sentence();
      await page.fill(`#${field.fieldName}-description`, description);
      filledFields?.set(`${field.fieldName}-description`, description);
    }
    // When verifying, check the description still holds the value
    // we typed during the fill pass.
    else if (filledFields?.has(`${field.fieldName}-description`)) {
      await expect(descriptionLocator).toHaveValue(
        filledFields!.get(`${field.fieldName}-description`)!
      );
    }
    return;
  }

  const fieldDOM = page.locator(`#${fieldId}`);
  await expect(fieldDOM).toBeVisible();

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
      let value: string;

      // Native HTML select: pick index 1 to skip the placeholder option.
      if (tag === 'select') {
        await page.selectOption(`#${fieldId}`, { index: 1 });
        value = await fieldDOM.inputValue();
      }
      // Date fields need a Finnish date format, e.g. "30.4.2026".
      else if (field.fieldName.endsWith('_date')) {
        value = finnishDate(field.fieldName === 'end_date' ? 2 : 1);
        await page.fill(`#${fieldId}`, value);
      }
      // Year fields get a random year.
      else if (field.fieldName.endsWith('_year')) {
        value = faker.number.int({ min: 1980, max: 2020 }).toString();
        await page.fill(`#${fieldId}`, value);
      }
      // Amount fields get a random number with decimals.
      else if (field?.format === 'decimal-number') {
        const decimal = faker.number.int({ min: 10, max: 99 }).toString();
        value = faker.number.int({ min: 1, max: 99999 }).toString();
        await page.fill(`#${fieldId}`, `${value},${decimal}`);
      }
      // Integer and number fields get a random whole number.
      else if (field.type === 'integer' || field.type === 'number') {
        value = faker.number.int({ min: 1, max: 999 }).toString();
        await page.fill(`#${fieldId}`, value);
      }
      // Everything else gets random filler text.
      else {
        value = faker.lorem.sentences(4);
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
 */
export async function verifyStep(
  page: Page,
  language: string,
  step: string,
  sections: Record<string, Record<string, StepField>>,
  t: (key: string) => string,
  shouldFill = false,
  filledFields?: FilledFields,
): Promise<void> {
  for (const [sectionIndex, [section, fields]] of Object.entries(sections).entries()) {
    const sectionTitle = t(`${section}.title`);
    const playwrightStepLabel = shouldFill ? 'Filling' : `Verifying (${language}) translations for`;
    await test.step(`${playwrightStepLabel} section: ${sectionTitle}...`, async () => {
      if (!sectionTitle) throw new Error(`No translation found for ${section}.title`);
      await expect(
        page.locator('h3.hdbt-form--section__title').nth(Number(sectionIndex))
      ).toContainText(sectionTitle);
    });

    // Track which conditional toggles and array groups have been handled
    // so we don't click "Add" or trigger the same condition twice.
    const triggeredConditions = new Set<string>();
    const addedArrays = new Set<string>();

    // Go through each field in current section one by one.
    for (const [, field] of Object.entries(fields)) {
      const fieldId = `root_${field.fieldPath.join('_')}`;
      const fieldTitle = field.titleKey;

      await test.step(`${playwrightStepLabel} field: ${t(fieldTitle)}`, () =>
        handleField(
          page, field, fieldId, fieldTitle,
          step, section, t, shouldFill,
          triggeredConditions, addedArrays, filledFields,
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
        await verifyStep(page, language, step, sections, t, false);
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

  const preview = page.locator('.hdbt-form__preview');
  await expect(preview).toBeVisible();

  // Loop through all fields and check each value appears in the preview.
  for (const [sectionKey, fields] of Object.entries(confirmStep)) {
    for (const [, field] of Object.entries(fields)) {
      const fieldId = `root_${field.fieldPath.join('_')}`;
      const value = filledFields.get(fieldId);
      if (!value || value === 'true' || value === 'false') continue;

      const fieldTitle = t(field.titleKey);
      const sectionTitle = t(`${sectionKey}.title`);

      // Build an exact-match pattern for the field title to avoid a short
      // title like "Vuosi" matching a longer label like "Vuosi, jolle haen".
      const exactFieldTitle = new RegExp(`^\\s*${fieldTitle.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*$`);

      await test.step(`Preview: ${fieldTitle} = ${value}`, async () => {

        // Narrow the search area to this section so we don't accidentally
        // find the value in a different section with the same field name.
        const sectionContainer = preview
          .locator('section.grants-form--preview-section')
          .filter({ has: page.locator('h4.hdbt-form--section__title', { hasText: sectionTitle }) });
        const scope = (await sectionContainer.count() > 0) ? sectionContainer : preview;

        // First try: some fields have their own section heading.
        // Check the value appears inside that section.
        const byFieldTitle = preview
          .locator('section.grants-form--preview-section')
          .filter({ has: page.locator('h4.hdbt-form--section__title', { hasText: fieldTitle }) });
        if (await byFieldTitle.count() > 0) {
          await expect(byFieldTitle.locator('.hdbt-form--section__content')).toContainText(value);
          return;
        }

        // Second try: find the field by its label span and check the
        // value appears next to it.
        const bySpanLabel = scope
          .locator('span.grants-form--preview-section__label')
          .filter({ hasText: exactFieldTitle })
          .locator('xpath=..')
          .first();

        if (await bySpanLabel.count() > 0) {
          await expect(bySpanLabel).toContainText(value);
          return;
        }

        // Last resort: check the value appears anywhere in the section.
        await expect(
          scope.locator('.hdbt-form--section__content').filter({ hasText: value }).first()
        ).toBeVisible();
      });
    }
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

  let missingInputs = {};

  // Submit the empty form first to trigger all required field errors.
  // This lets us verify that every required field shows an error message.
  await test.step('Gather form errors', async () => {
    if (!options.formURL) throw new Error(`The form URL is missing.`);
    await page.goto(options.formURL);
    await gatherRequiredFieldWarnings(page);
    missingInputs = page.locator('[class*="notification_hds-notification--error__"] li');
    await assertMissingInputsVisible(page);
  });

  for (const [languageIndex, language] of languages.entries()) {
    const fill = languageIndex === 0;
    const t = createTranslator(formData as FormPreviewResponse, language);

    for (const [, [step, sections]] of Object.entries(tree).entries()) {
      const stepTitle = t(`${step}.title`);
      await test.step(`Checking step: ${stepTitle}...`, async () => {
        if (!stepTitle) throw new Error(`No translation found for ${step}.title`);
        await expect(page.locator('button[aria-current="step"] p')).toContainText(stepTitle);
      });

      // @TODO Tarvii katsoo "attachment" filun nimi.

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
        return;
      }

      // Fill or verify this step's fields depending on the current pass.
      await verifyStep(page, language, step, sections, t, fill, filledFields);
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
  // @todo Fix the issue of injecting translated ATV responses to select fields.
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
): Promise<void> {

  // Open the form, navigate to the final step, accept the terms,
  // submit, and verify the confirmation page is shown.
  await test.step('Submit the form', async () => {
    if (!options.formCompletionURL) throw new Error(`The form completion URL is missing.`);
    if (!options.formURL) throw new Error(`The form URL is missing.`);
    await page.goto(options.formURL);
    // Expect the React application to load.
    await waitForFormLoad(page);
    const t = createTranslator(formData as FormPreviewResponse, 'fi');
    const step = 'confirm_and_submit';

    // Go to last step and check that the form can be submitted.
    await clickOnStepWithTitle(page, t, `${step}.title`);
    await expect(page.locator('h2.grants-form__page-title')).toContainText(t(`${step}.title`));

    const preview = page.locator('.hdbt-form__preview');
    await expect(preview).toBeVisible();

    const submitButton = page.locator('.hdbt-form--actions').getByRole('button', { name: t('submit') });
    await expect(submitButton).toBeVisible();
    await expect(submitButton).toHaveAttribute('disabled');

    const agreeTermsCheckbox = page.locator('label[for="final-acceptance"]');
    await expect(agreeTermsCheckbox).toBeVisible();
    await agreeTermsCheckbox.click();

    // Submit.
    logger('Attempting to submit the form...')
    await expect(submitButton).not.toHaveAttribute('disabled');
    await submitButton.click();

    // Verify the completion.
    await logCurrentUrl(page);
    await page.waitForURL(options.formCompletionURL);
    await expect(page.getByRole('heading', {name: 'Avustushakemus lähetetty onnistuneesti'})).toBeVisible();
    await expect(page.getByText('Lähetetty - odotetaan vahvistusta').first()).toBeVisible();

    // Attempt to locate the "Vastaanotettu" text on the page. Keep polling for 60000ms (1 minute).
    // Note: We do this instead of using Playwrights "expect" method so that test execution isn't interrupted if this fails.
    const applicationReceived = await waitForTextWithInterval(page, 'Vastaanotettu');
    if (!applicationReceived) {
      logger('WARNING: Failed to validate that the application was received.');
      return;
    }
  });
}

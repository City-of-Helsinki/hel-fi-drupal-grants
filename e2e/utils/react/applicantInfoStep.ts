import { expect, type Locator, type Page, test} from '@playwright/test';
import { fakerFI as faker } from '@faker-js/faker';
import { buildFormTree } from './stepInspector';
import { createTranslator } from './utils';
import { type FilledFields } from './formFieldVerifier';
import type { FormPreviewResponse } from './schemaFetcher';
import { getFakeEmailAddress } from '../field_helpers';
import { selectFirstDropdownOption } from "./fieldFillers";

/**
 * Handle a text field.
 *
 * Fills a plain text input or checks it still holds the expected value.
 *
 * When fill is true, calls generateValue to produce a value, types it
 * in, and returns it. When fill is false, asserts the field still
 * contains the value stored from the fill pass.
 *
 * @param page
 *   The Playwright page instance.
 * @param fieldId
 *   The HTML id of the input field.
 * @param fill
 *   True to fill the field, false to verify it.
 * @param generateValue
 *   A function that returns the value to type in.
 * @param filledFields
 *   The map of previously filled values used during verification.
 */
async function handleTextField(
  page: Page,
  fieldId: string,
  fill: boolean,
  generateValue: () => string,
  filledFields?: FilledFields,
): Promise<string | undefined> {
  if (fill) {
    const value = generateValue();
    await page.fill(`#${fieldId}`, value);
    return value;
  }
  await expect(page.locator(`#${fieldId}`)).toHaveValue(filledFields!.get(fieldId)!);
}

/**
 * Handle dropdown field.
 *
 * Fill HDS dropdown by picking its first option, or checks it
 * still shows the expected value from the fill pass.
 *
 * @param page
 *   The Playwright page instance.
 * @param fieldId
 *   The HTML id of the dropdown container.
 * @param fieldDOM
 *   The Playwright locator for the dropdown container.
 * @param fill
 *   True to pick an option, false to verify the current selection.
 * @param filledFields
 *   The map of previously filled values used during verification.
 */
async function handleDropdownField(
  page: Page,
  fieldId: string,
  fieldDOM: Locator,
  fill: boolean,
  filledFields?: FilledFields,
): Promise<string | undefined> {
  if (fill) {
    return await selectFirstDropdownOption(page, fieldDOM, fieldId);
  }
  await expect(page.locator(`#${fieldId}-main-button`)).toContainText(filledFields!.get(fieldId)!);
}

/**
 * Handle "community officials" fields.
 *
 * Fills the two community officials dropdowns, or verifies their values.
 *
 * The officials list needs two items. After picking the first option,
 * the "Add" button is clicked to reveal the second row before filling it.
 * Returns the label selector for the last official field, which is used
 * to check the field label translation after filling.
 *
 * @param page
 *   The Playwright page instance.
 * @param fill
 *   True to pick options, false to verify the current selections.
 * @param filledFields
 *   The map of previously filled values used during verification.
 */
async function handleOfficialField(
  page: Page,
  fill: boolean,
  filledFields?: FilledFields,
): Promise<string> {
  let fieldLabel = '';
  for (let i = 0; i < 2; i++) {
    const officialId = `root_applicant_info_community_officials_community_officials_${i}_official`;
    const officialDOM = page.locator(`#${officialId}`);
    await expect(officialDOM).toBeVisible();
    fieldLabel = `label[for="${officialId}-main-button"]`;
    if (fill) {
      await officialDOM.locator(`#${officialId}-main-button`).click();
      const officialFirstOption = officialDOM.locator('[role="option"]').nth(i);
      await expect(officialFirstOption).toBeVisible();
      const officialOptionText = (await officialFirstOption.textContent()) ?? '';
      await officialFirstOption.click();
      await page.keyboard.press('Escape');
      await expect(page.locator(`#${officialId}-main-button`)).toHaveAttribute('aria-expanded', 'false');

      // After filling the first official, click "Add" to show the second row.
      if (i === 0) {
        const addMoreButton = await page
          .locator(`#${officialId}`)
          .locator('xpath=ancestor::div[contains(@class,"field-array")]')
          .getByRole('button', {name: /Add|Lägg till|Lisää/i});
        await expect(addMoreButton).toBeVisible();
        await addMoreButton.click();
      }
      // Store only the person's name, stripping the role label in
      // parentheses so the preview check matches the displayed value.
      filledFields?.set(officialId, officialOptionText.trim().replace(/\s*\([^)]*\)$/, ''));
      fieldLabel = `label[for="${officialId}-main-button"]`;
    } else {
      await expect(page.locator(`#${officialId}-main-button`)).toContainText(filledFields!.get(officialId)!);
    }
  }

  return fieldLabel;
}

/**
 * Verify "Applicant info" step field translations.
 *
 * Checks that every field label and tooltip shows the correct
 * translated text for the given language.
 *
 * @param page
 *   The Playwright page instance.
 * @param formData
 *   The form schema and translations fetched from the server.
 * @param language
 *   The language code to verify. Defaults to 'fi'.
 */
export async function verifyApplicantInfoStepFieldTranslations(
  page: Page,
  formData: Pick<FormPreviewResponse, 'schema' | 'ui_schema' | 'translations'>,
  language = 'fi',
) {
  const tree = buildFormTree(formData as any);
  const t = createTranslator(formData as FormPreviewResponse, language);
  const sections = tree['applicant_info'];

  await test.step(`Verify step: ${t('applicant_info.title')}`, async () => {
    // Go through each section and verify its title and field labels.
    for (const [sectionIndex, [section, fields]] of Object.entries(sections).entries()) {
      const sectionTitle = t(`${section}.title`);
      await test.step(`Verifying translations for section: ${sectionTitle}...`, async () => {
        if (!sectionTitle) throw new Error(`No translation found for ${section}.title`);
        await expect(
          page.locator('h3.hdbt-form--section__title').nth(Number(sectionIndex))
        ).toContainText(sectionTitle);
      });

      // Go through each field in this section and check its label.
      for (const [, field] of Object.entries(fields)) {
        const fieldId = `root_${field.fieldPath.join('_')}`;
        const fieldTitle = field.titleKey;

        await test.step(`Verifying translations for field: ${t(fieldTitle)}...`, async () => {
          const fieldDOM = page.locator(`#${fieldId}`);
          await expect(fieldDOM).toBeVisible();
          let fieldLabel = `label[for="${fieldId}"]`;

          if (['community_address', 'bank_account', 'official'].includes(field.fieldName)) {
            fieldLabel = `label[for="${fieldId}-main-button"]`;
          }

          // Check that the field label contains correct translation.
          await expect(page.locator(fieldLabel)).toContainText(t(fieldTitle));

          // If the field has a tooltip,
          // check that it contains correct translations.
          if (field.tooltipLabel) {
            await expect(
              page.locator(`${fieldLabel} ~ div button[class*="tooltipButton"]`)
            ).toBeVisible();
          }
        });
      }
    }
  });
}

/**
 * Fills or verifies all fields on the applicant info step.
 *
 * When fill is true, types valid values into each field and records
 * them. When fill is false, checks that each field still shows the
 * value from the fill pass.
 *
 * @param page
 *   The Playwright page instance.
 * @param formData
 *   The form schema and translations fetched from the server.
 * @param language
 *   The language currently being tested. Defaults to 'fi'.
 * @param fill
 *   True to fill fields, false to verify them.
 * @param filledFields
 *   The map to store or read filled values from.
 */
export async function fillApplicantInfoStep(
  page: Page,
  formData: Pick<FormPreviewResponse, 'schema' | 'ui_schema' | 'translations'>,
  language = 'fi',
  fill = false,
  filledFields?: FilledFields,
) {
  const tree = buildFormTree(formData as any);
  const t = createTranslator(formData as FormPreviewResponse, language);
  const sections = tree['applicant_info'];

  await test.step(`Fill step: ${t('applicant_info.title')}`, async () => {
    // Go through each section, verify its title, then fill its fields.
    for (const [sectionIndex, [section, fields]] of Object.entries(sections).entries()) {
      const sectionTitle = t(`${section}.title`);
      await test.step(`Filling section: ${sectionTitle}...`, async () => {
        if (!sectionTitle) throw new Error(`No translation found for ${section}.title`);
        await expect(
          page.locator('h3.hdbt-form--section__title').nth(Number(sectionIndex))
        ).toContainText(sectionTitle);
      });

      // Go through each field and fill or verify it based on its type.
      for (const [, field] of Object.entries(fields)) {
        const fieldId = `root_${field.fieldPath.join('_')}`;
        const fieldTitle = field.titleKey;

        await test.step(`Filling field: ${t(fieldTitle)}...`, async () => {
          const fieldDOM = page.locator(`#${fieldId}`);
          await expect(fieldDOM).toBeVisible();

          let fieldValue: string | undefined;
          let fieldLabel = `label[for="${fieldId}"]`;

          // Each field type needs a different fill strategy.
          switch (field.fieldName) {
            case 'email':
              fieldValue = await handleTextField(page, fieldId, fill, getFakeEmailAddress, filledFields);
              break;
            case 'contact_person':
              fieldValue = await handleTextField(page, fieldId, fill, () => faker.person.fullName(), filledFields);
              break;
            case 'contact_person_phone_number':
              fieldValue = await handleTextField(page, fieldId, fill, () => `0${faker.number.int({
                min: 40,
                max: 50
              })} ${faker.number.int({
                min: 100,
                max: 999
              })} ${faker.number.int({min: 1000, max: 9999})}`, filledFields);
              break;
            case 'community_address':
            case 'bank_account':
              fieldLabel = `label[for="${fieldId}-main-button"]`;
              fieldValue = await handleDropdownField(page, fieldId, fieldDOM, fill, filledFields);
              break;
            case 'official':
              fieldLabel = await handleOfficialField(page, fill, filledFields);
              break;
            default:
              break;
          }

          // Check that the field label contains correct translation.
          await expect(page.locator(fieldLabel)).toContainText(t(fieldTitle));

          // Check for tooltip.
          if (field.tooltipLabel) {
            await expect(
              page.locator(`${fieldLabel} ~ div button[class*="tooltipButton"]`)
            ).toBeVisible();
          }

          // Store the filled value so it can be verified in later passes.
          if (fill && fieldValue !== undefined) {
            filledFields?.set(fieldId, fieldValue);
          }
        });
      }
    }
  });
}

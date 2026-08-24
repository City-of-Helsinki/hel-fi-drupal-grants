import { expect } from '@playwright/test';
import type { FormLogic } from '../../../utils/react/formFieldVerifier';

/**
 * Form-specific logic for fields the generic engine can't handle.
 *
 * In this form an over 50 000 € subvention total unlocks the multi-year grant
 * duration radio buttons. The chosen duration in turn reveals the per-year
 * amount and description fields.
 */
export const formLogic: FormLogic = {
  grant_info_step: {
    subventions_section: {
      // Fill a subvention that enables the multi-year durations.
      subventions: async ({ page, fieldId, field, shouldFill, filledFields }) => {
        if (shouldFill && field.options?.length) {
          const optionId = `${fieldId}-${field.options[0].id}`;
          const amount = '60000';
          await page.fill(`#${optionId}`, amount);
          filledFields?.set(optionId, amount);
        }
        return true;
      },
    },
  },
  information_in_more_detail_step: {
    information_in_more_detail_section: {
      // Select the three-year duration option.
      project_plan_grant_duration: async ({ page, fieldId, shouldFill }) => {
        if (shouldFill) {
          const option = page.locator(`label[for="${fieldId}_3"]`);
          await expect(option).toBeVisible();
          await option.click();
        }
        return true;
      },
    },
    target_groups_section: {
      assesment_2_fieldset: {
        // Select an area in the optional area picker.
        location_1: async ({ page, fieldId, shouldFill }) => {
          if (shouldFill) {
            await page.locator(`#${fieldId}-main-button`).click();
            const area = page.locator(`#${fieldId} [role="option"]`).nth(1);
            await expect(area).toBeVisible();
            await area.click();
            await page.keyboard.press('Escape');
            await expect(page.locator(`#${fieldId}-main-button`)).toHaveAttribute('aria-expanded', 'false');
          }
          return true;
        },
        location_2: async () => true,
        location_3: async () => true,
        location_4: async () => true,
        location_5: async () => true,
      },
    },
  },
};

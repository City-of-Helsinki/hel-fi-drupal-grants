import type { FormLogic } from '../../../utils/react/formFieldVerifier';

// Subvention amount that shows the salary fields.
const SUBVENTION_AMOUNT = 1000;

/**
 * Form-specific logic for the nuorisotoiminnan palkkausavustus form.
 *
 * The salary fields show only when the salary subvention has an amount, and
 * the premises list shows only when the activity is not only digital.
 */
export const formLogic: FormLogic = {
  grant_info_step: {
    subventions_section: {
      // Fill the salary subvention amount on the fill pass only.
      subventions: async ({ page, fieldId, field, shouldFill, filledFields }) => {
        if (!shouldFill || !field.options?.length) return false;
        const optionId = `${fieldId}-${field.options[1].id}`;
        await page.fill(`#${optionId}`, String(SUBVENTION_AMOUNT));
        filledFields?.set(optionId, String(SUBVENTION_AMOUNT));
        return true;
      },
    },
  },
  community_activity_step: {
    premises_section: {
      // Answer "Ei" to show the premises list.
      digital_activity_only: false,
    },
  },
  salary_info_step: {
    // Total of the filled salary subvention.
    salary_subvention_sum: SUBVENTION_AMOUNT,
  },
};

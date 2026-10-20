import type { FormLogic } from '../../../utils/react/formFieldVerifier';

// Subvention amount that shows the multi-year grant question and the activity basis fields.
const SUBVENTION_AMOUNT = 5001;

/**
 * Form-specific logic for the kuva toiminta-avustus form.
 *
 * The multi-year grant question and the activity basis fields show only
 * when the applied subvention is over 5000 €.
 */
export const formLogic: FormLogic = {
  grant_info_step: {
    subventions_section: {
      // Fill the subvention amount on the fill pass only.
      subventions: async ({ page, fieldId, field, shouldFill, filledFields }) => {
        if (!shouldFill || !field.options?.length) return false;
        const optionId = `${fieldId}-${field.options[0].id}`;
        await page.fill(`#${optionId}`, String(SUBVENTION_AMOUNT));
        filledFields?.set(optionId, String(SUBVENTION_AMOUNT));
        return true;
      },
    },
    // Total of the filled subvention.
    subventions_total: SUBVENTION_AMOUNT,
  },
};

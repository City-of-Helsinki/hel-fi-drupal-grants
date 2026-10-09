import type { FormLogic } from '../../../utils/react/formFieldVerifier';

// Subvention amount that shows the activity foundation fields.
const SUBVENTION_AMOUNT = 5000;

/**
 * Form-specific logic for the kuva projektiavustus form.
 *
 * The activity foundation fields show only when the applied subvention
 * is at least 5000 €.
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
  },
  activity_foundations_step: {
    // Total of the filled subvention.
    subventions_total: SUBVENTION_AMOUNT,
  },
};

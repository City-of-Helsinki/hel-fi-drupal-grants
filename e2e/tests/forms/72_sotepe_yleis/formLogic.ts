import type { FormLogic } from '../../../utils/react/formFieldVerifier';

// Subvention amount that shows all the purpose fields.
const SUBVENTION_AMOUNT = 50000;

/**
 * Form-specific logic for the sotepe yleisavustus form.
 *
 * Fields like "Ketkä toiminnastanne erityisesti hyötyvät?" show only when
 * the applied subvention is large enough.
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
    purpose_section: {
      // Total of the filled subvention.
      subventions_total: SUBVENTION_AMOUNT,
    },
  },
};

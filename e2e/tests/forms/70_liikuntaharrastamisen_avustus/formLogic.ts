import type { FormLogic } from '../../../utils/react/formFieldVerifier';

/**
 * Form-specific logic for the liikuntaharrastamisen avustus form.
 *
 * The application type and duration radios open more fields the later
 * the option, so pick the option that opens the most fields.
 */
export const formLogic: FormLogic = {
  information_in_more_detail_step: {
    information_in_more_detail_section: {
      // Select the new project option to reveal the duration choice.
      project_application_type: '2',
      // Select the two-year duration to reveal the second year fields.
      grant_duration: '2',
    },
  },
};

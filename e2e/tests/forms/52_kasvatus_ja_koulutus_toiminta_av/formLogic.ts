import type { FormLogic } from '../../../utils/react/formFieldVerifier';

/**
 * Form-specific logic for the kasvatus ja koulutus toiminta-avustus form.
 *
 * The rent fields of a location show only when the location is not
 * held in a space provided by the city.
 */
export const formLogic: FormLogic = {
  community_activity_step: {
    locations_info_section: {
      locations_info: {
        0: {
          // Answer "Ei" to show the rent fields.
          location_held_in_provided_space: false,
        },
      },
    },
  },
};

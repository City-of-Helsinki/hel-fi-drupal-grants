import { fakerFI as faker } from '@faker-js/faker/locale/index';
import { FieldInputs } from '../../../utils/react/formFieldVerifier';

/**
 * Override fill values.
 *
 * Nest the keys as step -> section -> field, matching the field's path in the
 * form's schema.json.
 * A value can be either a plain string or a function returning one, so you can
 * use a faker expression like `() => faker.lorem.words(30)`.
 */
export const formInputs: FieldInputs = {
  information_in_more_detail_step: {
    grant_target_section: {
      safety_practices: 'Testailua.',
      safer_club_practices: () => faker.lorem.words(30),
    },
  },
};

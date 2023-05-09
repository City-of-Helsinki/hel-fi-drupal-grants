<?php

namespace Drupal\grants_profile\Plugin\Validation\Constraint;

use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ValidIban constraint.
 */
class RequiredIfRegisteredValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, $constraint) {
    if (!$this->isRequired($value)) {
      $this->context->addViolation($constraint->requiredMissing, ['%value' => $value]);
    }
  }

  /**
   * Is value valid IBAN.
   *
   * @param string|null $value
   *   Value to be validated.
   *
   * @return bool
   *   If value is conditionally required.
   */
  private function isRequired(?string $value): bool {

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');

    $applicantType = $grantsProfileService->getApplicantType();

    if ($applicantType == 'registered_community') {
      if (empty($value)) {
        return FALSE;
      }
      return TRUE;
    }
    // All other scenarios return true.
    return TRUE;
  }

}

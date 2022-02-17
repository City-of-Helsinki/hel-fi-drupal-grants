<?php

namespace Drupal\grants_profile\Plugin\Validation\Constraint;

use PHP_IBAN\IBAN;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ValidIban constraint.
 */
class ValidIbanValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, $constraint) {
    if (!$this->isValidIban($value)) {
      $this->context->addViolation($constraint->notValidIban, ['%value' => $value]);
    }
  }

  /**
   * Is value valid IBAN.
   *
   * @param string $value
   *   Value to be validated.
   *
   * @return bool
   *   If value is valid IBAN.
   */
  private function isValidIban(string $value): bool {
    $myIban = new IBAN($value);
    return $myIban->Verify();
  }

}

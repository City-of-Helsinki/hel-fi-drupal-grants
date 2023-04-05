<?php

namespace Drupal\grants_profile\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate empty values. Custom class to override default.
 */
class NotEmptyValueValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$constraint instanceof NotEmptyValue) {
      throw new UnexpectedTypeException($constraint, NotEmptyValue::class);
    }

    if (FALSE === $value || (empty($value) && '0' !== $value && 0 !== $value)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ value }}', $this->formatValue($value))
        ->setCode(NotEmptyValue::IS_BLANK_ERROR)
        ->addViolation();
    }
  }

}

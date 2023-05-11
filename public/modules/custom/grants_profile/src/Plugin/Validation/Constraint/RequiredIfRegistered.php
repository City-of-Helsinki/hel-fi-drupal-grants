<?php

namespace Drupal\grants_profile\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is valid IBAN number.
 *
 * @Constraint(
 *   id = "RequiredIfRegistered",
 *   label = @Translation("Required value if applicant type is registered", context = "Validation"),
 *   type = "string"
 * )
 */
class RequiredIfRegistered extends Constraint {

  /**
   * The message that will be shown if the value is not unique.
   *
   * @var string
   */
  public string $requiredMissing = '%value is required';

}

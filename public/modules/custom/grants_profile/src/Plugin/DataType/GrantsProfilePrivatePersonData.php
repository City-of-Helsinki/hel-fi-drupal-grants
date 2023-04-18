<?php

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Address DataType.
 *
 * @DataType(
 * id = "grants_profile_private_person",
 * label = @Translation("Grants Profile PP"),
 * definition_class =
 *   "\Drupal\grants_profile\TypedData\Definition\GrantsProfilePrivatePersonDefinition"
 * )
 */
class GrantsProfilePrivatePersonData extends Map {

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {

    parent::setValue($values, $notify);
  }

  /**
   * This is where we could validate custom fields, bank accounts etc.
   */
  public function validate(): ConstraintViolationListInterface {
    $parentResults = parent::validate();

    return $parentResults;
  }

}

<?php

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Address DataType.
 *
 * @DataType(
 * id = "grants_profile_profile",
 * label = @Translation("Grants Profile"),
 * definition_class =
 *   "\Drupal\grants_profile\TypedData\Definition\GrantsProfileDefinition"
 * )
 */
class GrantsProfileData extends Map {

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {

    if ($values['companyStatusSpecial'] == NULL) {
      $values['companyStatusSpecial'] = '';
    }
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

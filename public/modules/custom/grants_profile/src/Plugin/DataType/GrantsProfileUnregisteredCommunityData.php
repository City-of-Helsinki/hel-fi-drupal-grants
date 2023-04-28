<?php

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Address DataType.
 *
 * @DataType(
 * id = "grants_profile_unregistered_community",
 * label = @Translation("Grants Profile UC"),
 * definition_class =
 *   "\Drupal\grants_profile\TypedData\Definition\GrantsProfileUnregisteredCommunityDefinition"
 * )
 */
class GrantsProfileUnregisteredCommunityData extends Map {

  /**
   * This is where we could validate custom fields, bank accounts etc.
   */
  public function validate(): ConstraintViolationListInterface {
    $parentResults = parent::validate();

    return $parentResults;
  }

}

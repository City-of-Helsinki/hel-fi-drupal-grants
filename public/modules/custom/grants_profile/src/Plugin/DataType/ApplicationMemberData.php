<?php

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Application Member DataType.
 *
 * @DataType(
 * id = "grants_profile_application_member",
 * label = @Translation("Application Member"),
 * definition_class =
 *   "\Drupal\grants_profile\TypedData\Definition\ApplicationMemberDefinition"
 * )
 */
class ApplicationMemberData extends Map {

  /**
   * Set field value.
   *
   * @param array $values
   *   Values for the field.
   */
  public function setValues(array $values): void {
    $this->values = $values;
  }

}

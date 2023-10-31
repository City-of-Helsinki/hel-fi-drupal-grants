<?php

namespace Drupal\grants_members\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_metadata\Plugin\DataType\DataFormatTrait;

/**
 * Members DataType.
 *
 * @DataType(
 * id = "grants_members",
 * label = @Translation("Members"),
 * definition_class =
 *   "\Drupal\grants_members\TypedData\Definition\GrantsMembersDefinition"
 * )
 */
class GrantsMembersData extends Map {

  use DataFormatTrait;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $retval = parent::getValue();
    return $retval;
  }

  /**
   * Get values from parent.
   *
   * @return array
   *   The values.
   */
  public function getValues(): array {
    return $this->values;
  }

}

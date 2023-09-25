<?php

namespace Drupal\grants_orienteering_map\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_metadata\Plugin\DataType\DataFormatTrait;

/**
 * Premises DataType.
 *
 * @DataType(
 * id = "grants_orienteering_map",
 * label = @Translation("Orienteering Map"),
 * definition_class =
 *   "\Drupal\grants_orienteering_map\TypedData\Definition\OrienteeringMapDefinition"
 * )
 */
class OrienteeringMapData extends Map {

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

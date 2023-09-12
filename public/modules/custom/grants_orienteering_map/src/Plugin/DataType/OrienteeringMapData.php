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
   * Make sure boolean values are handled correctly.
   *
   * @param array $values
   *   All values.
   * @param bool $notify
   *   Notify this value change.
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
  }

}

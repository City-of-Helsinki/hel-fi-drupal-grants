<?php

namespace Drupal\grants_place_of_operation\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_metadata\Plugin\DataType\DataFormatTrait;

/**
 * Premises DataType.
 *
 * @DataType(
 * id = "grants_place_of_operation",
 * label = @Translation("Place of operation"),
 * definition_class =
 *   "\Drupal\grants_place_of_operation\TypedData\Definition\PlaceOfOperationDefinition"
 * )
 */
class PlaceOfOperationData extends Map {

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

    if (isset($values["free"])) {

      if ($values["free"] === "false" || $values["free"] === "0") {
        $values["free"] = FALSE;
      }

      if ($values["free"] === "true" || $values["free"] === "1") {
        $values["free"] = TRUE;
      }

      if ($values["free"] === "") {
        unset($values["free"]);
      }
    }

    parent::setValue($values, $notify);
  }

}

<?php

namespace Drupal\grants_premises\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_metadata\Plugin\DataType\DataFormatTrait;

/**
 * Premises DataType.
 *
 * @DataType(
 * id = "grants_premises",
 * label = @Translation("Premises"),
 * definition_class = "\Drupal\grants_premises\TypedData\Definition\GrantsPremisesDefinition"
 * )
 */
class GrantsPremisesData extends Map {

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

    if ($values["isOwnedByCity"] === "false") {
      $values["isOwnedByCity"] = 0;
    }
    if ($values["isOwnedByCity"] === "true") {
      $values["isOwnedByCity"] = 1;
    }

    if ($values["isOthersUse"] === "false") {
      $values["isOthersUse"] = 0;
    }
    if ($values["isOthersUse"] === "true") {
      $values["isOthersUse"] = 1;
    }

    if ($values["isOwnedByApplicant"] === "false") {
      $values["isOwnedByApplicant"] = 0;
    }
    if ($values["isOwnedByApplicant"] === "true") {
      $values["isOwnedByApplicant"] = 1;
    }

    if ($values["free"] === "false") {
      $values["free"] = 0;
    }
    if ($values["free"] === "true") {
      $values["free"] = 1;
    }
    if ($values["free"] === "") {
      unset($values["free"]);
    }

    parent::setValue($values, $notify);
  }

}

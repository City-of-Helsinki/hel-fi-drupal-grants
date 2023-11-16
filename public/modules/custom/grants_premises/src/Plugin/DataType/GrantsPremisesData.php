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
 * definition_class =
 *   "\Drupal\grants_premises\TypedData\Definition\GrantsPremisesDefinition"
 * )
 */
class GrantsPremisesData extends Map {

  use DataFormatTrait;

  /**
   * Parse vague values from Premises data to expected values.
   *
   * @param array $values
   *   Array to parse.
   * @param string $key
   *   Key to parse.
   *
   * @return void
   *   Return void.
   */
  private static function parseVagueValue(array &$values, string $key) : void {
    if (array_key_exists($key, $values)) {
      if ($values[$key] === "false" || $values[$key] === "0") {
        $values[$key] = FALSE;
      }
      elseif ($values[$key] === "true" || $values[$key] === "1") {
        $values[$key] = TRUE;
      }
      elseif ($values[$key] === NULL || $values[$key] === "") {
        unset($values[$key]);
      }
    }
  }

  /**
   * Make sure boolean values are handled correctly.
   *
   * @param array|null $values
   *   All values.
   * @param bool $notify
   *   Notify this value change.
   *
   * @return void
   *   Return void.
   */
  public function setValue($values, $notify = TRUE) : void {
    if (isset($values)) {
      self::parseVagueValue($values, 'isOwnedByCity');
      self::parseVagueValue($values, 'isOthersUse');
      self::parseVagueValue($values, 'isOwnedByApplicant');
      self::parseVagueValue($values, 'free');
    }

    parent::setValue($values, $notify);
  }

}

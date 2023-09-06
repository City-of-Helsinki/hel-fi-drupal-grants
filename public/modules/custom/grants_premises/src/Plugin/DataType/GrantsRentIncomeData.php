<?php

namespace Drupal\grants_premises\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_metadata\Plugin\DataType\DataFormatTrait;

/**
 * Grants Rent Income Data.
 *
 * @DataType(
 * id = "grants_rent_income",
 * label = @Translation("Taide ja kulttuuri: taiteen perusopetuksen avustus"),
 * definition_class =
 *   "\Drupal\grants_premises\TypedData\Definition\GrantsRentIncomeDefinition"
 * )
 */
class GrantsRentIncomeData extends Map {

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

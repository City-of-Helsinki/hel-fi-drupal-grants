<?php

namespace Drupal\grants_premises\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_metadata\Plugin\DataType\DataFormatTrait;

/**
 * Grants Rent Cost Data.
 *
 * @DataType(
 * id = "grants_rent_cost",
 * label = @Translation("Grants rent cost"),
 * definition_class =
 *   "\Drupal\grants_premises\TypedData\Definition\GrantsRentCostDefinition"
 * )
 */
class GrantsRentCostData extends Map {

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

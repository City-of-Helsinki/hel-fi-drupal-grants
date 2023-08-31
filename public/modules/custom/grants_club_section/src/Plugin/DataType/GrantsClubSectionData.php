<?php

namespace Drupal\grants_club_section\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_metadata\Plugin\DataType\DataFormatTrait;

/**
 * Club Section DataType.
 *
 * @DataType(
 * id = "grants_club_section",
 * label = @Translation("Club sections"),
 * definition_class =
 *   "\Drupal\grants_club_section\TypedData\Definition\GrantsClubSectionDefinition"
 * )
 */
class GrantsClubSectionData extends Map {

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

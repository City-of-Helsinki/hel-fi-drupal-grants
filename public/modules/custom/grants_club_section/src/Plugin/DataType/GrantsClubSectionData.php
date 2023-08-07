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

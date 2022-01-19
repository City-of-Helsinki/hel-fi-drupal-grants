<?php

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Address DataType.
 *
 * @DataType(
 * id = "grants_profile_profile",
 * label = @Translation("Grants Profile"),
 * definition_class = "\Drupal\grants_profile\TypedData\Definition\GrantsProfileDefinition"
 * )
 */
class GrantsProfileData extends Map {

}

<?php

declare(strict_types=1);

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_profile\TypedData\Definition\GrantsProfilePrivatePersonDefinition;

/**
 * Address DataType.
 */
#[DataType(
  id: 'grants_profile_private_person',
  label: new TranslatableMarkup('Grants Profile PP'),
  definition_class: GrantsProfilePrivatePersonDefinition::class
)]
class GrantsProfilePrivatePersonData extends Map {
}

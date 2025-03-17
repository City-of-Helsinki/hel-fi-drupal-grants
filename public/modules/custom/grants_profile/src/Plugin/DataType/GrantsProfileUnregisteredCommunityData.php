<?php

declare(strict_types=1);

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_profile\TypedData\Definition\GrantsProfileUnregisteredCommunityDefinition;

/**
 * Address DataType.
 */
#[DataType(
  id: "grants_profile_unregistered_community",
  label: new TranslatableMarkup('Grants Profile UC'),
  definition_class: GrantsProfileUnregisteredCommunityDefinition::class
)]
class GrantsProfileUnregisteredCommunityData extends Map {
}

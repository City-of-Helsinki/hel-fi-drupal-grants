<?php

declare(strict_types=1);

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_profile\TypedData\Definition\AddressDefinition;

/**
 * Address DataType.
 */
#[DataType(
  id: 'grants_profile_address',
  label: new TranslatableMarkup('Address'),
  definition_class: AddressDefinition::class
)]
class AddressData extends Map {
}

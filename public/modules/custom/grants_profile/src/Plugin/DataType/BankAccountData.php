<?php

declare(strict_types=1);

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_profile\TypedData\Definition\BankAccountDefinition;

/**
 * Address DataType.
 */
#[DataType(
  id: 'grants_profile_bank_account',
  label: new TranslatableMarkup('Bank Account'),
  definition_class: BankAccountDefinition::class
)]
class BankAccountData extends Map {
}

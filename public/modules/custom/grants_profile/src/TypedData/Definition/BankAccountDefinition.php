<?php

declare(strict_types=1);

namespace Drupal\grants_profile\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define bank account data.
 */
class BankAccountDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['bankAccount'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'bankAccountsArray',
          'bankAccount',
        ])
        ->addConstraint('NotEmptyValue')
        ->addConstraint('ValidIban');

      $info['ownerName'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'grantsProfile',
          'bankAccountsArray',
          'ownerName',
        ]);

      $info['ownerSsn'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'grantsProfile',
          'bankAccountsArray',
          'ownerSsn',
        ]);

      $info['confirmationFile'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'bankAccountsArray',
          'confirmationFile',
        ])
        ->addConstraint('NotEmptyValue');

    }
    return $this->propertyDefinitions;
  }

}

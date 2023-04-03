<?php

namespace Drupal\grants_profile\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Define address data.
 */
class GrantsProfileDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['companyNameShort'] = DataDefinition::create('string')
        ->setLabel('companyNameShort')
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyNameShort',
        ]);

      $info['companyName'] = DataDefinition::create('string')
        ->setLabel('companyName')
        ->setReadOnly(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyName',
        ]);

      $info['companyHome'] = DataDefinition::create('string')
        ->setLabel('companyHome')
        ->setReadOnly(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyHome',
        ]);

      $info['companyHomePage'] = DataDefinition::create('string')
        ->setLabel('companyHomePage')
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyHomePage',
        ]);

      $info['companyEmail'] = DataDefinition::create('string')
        ->setLabel('companyEmail')
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyEmail',
        ]);

      $info['companyStatus'] = DataDefinition::create('string')
        ->setLabel('companyStatus')
        ->setReadOnly(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyStatus',
        ]);

      $info['companyStatusSpecial'] = DataDefinition::create('string')
        ->setLabel('companyStatusSpecial')
        ->setReadOnly(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyStatusSpecial',
        ]);

      $info['businessPurpose'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('businessPurpose')
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'businessPurpose',
        ]);

      $info['foundingYear'] = DataDefinition::create('string')
        ->setRequired(FALSE)
        ->setLabel('foundingYear')
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'foundingYear',
        ]);

      $info['registrationDate'] = DataDefinition::create('string')
        ->setLabel('registrationDate')
        ->setReadOnly(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'registrationDate',
        ]);

      $info['officials'] = ListDataDefinition::create('grants_profile_application_official')
        ->setRequired(FALSE)
        ->setSetting('jsonPath', ['grantsProfile', 'officialsArray'])
        ->setLabel('Persons responsible for operations');

      $info['addresses'] = ListDataDefinition::create('grants_profile_address')
        ->setRequired(TRUE)
        ->setSetting('jsonPath', ['grantsProfile', 'addressesArray'])
        ->setLabel('Addresses');

      $info['bankAccounts'] = ListDataDefinition::create('grants_profile_bank_account')
        ->setRequired(TRUE)
        ->setSetting('jsonPath', ['grantsProfile', 'bankAccountsArray'])
        ->setLabel('Bank account numbers');

    }
    return $this->propertyDefinitions;
  }

}

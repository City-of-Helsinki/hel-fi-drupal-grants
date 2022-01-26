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
        ->setReadOnly(true)
        ->setLabel('companyNameShort')
        ->setSetting('jsonPath', ['grantsProfile', 'profileInfoArray', 'companyNameShort']);

      $info['companyName'] = DataDefinition::create('string')
        ->setLabel('companyName')
        ->setReadOnly(true)
        ->setSetting('jsonPath', ['grantsProfile', 'profileInfoArray', 'companyName']);

      $info['companyHome'] = DataDefinition::create('string')
        ->setLabel('companyHome')
        ->setReadOnly(true)
        ->setSetting('jsonPath', ['grantsProfile', 'profileInfoArray', 'companyHome']);

      $info['companyHomePage'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('companyHomePage')
        ->setSetting('jsonPath', ['grantsProfile', 'profileInfoArray', 'companyHomePage']);

      $info['companyEmail'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('companyEmail')
        ->setSetting('jsonPath', ['grantsProfile', 'profileInfoArray', 'companyEmail']);

      $info['companyStatus'] = DataDefinition::create('string')
        ->setLabel('companyStatus')
        ->setReadOnly(true)
        ->setSetting('jsonPath', ['grantsProfile', 'profileInfoArray', 'companyStatus']);

      $info['companyStatusSpecial'] = DataDefinition::create('string')
        ->setLabel('companyStatusSpecial')
        ->setReadOnly(true)
        ->setSetting('jsonPath', ['grantsProfile', 'profileInfoArray', 'companyStatusSpecial']);

      $info['businessPurpose'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('businessPurpose')
        ->setSetting('jsonPath', ['grantsProfile', 'profileInfoArray', 'businessPurpose']);

      $info['foundingYear'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('foundingYear')
        ->setSetting('jsonPath', ['grantsProfile', 'profileInfoArray', 'foundingYear']);

      $info['registrationDate'] = DataDefinition::create('string')
        ->setLabel('registrationDate')
        ->setReadOnly(true)
        ->setSetting('jsonPath', ['grantsProfile', 'profileInfoArray', 'registrationDate']);

      $info['officials'] = ListDataDefinition::create('grants_profile_application_official')
//        ->setRequired(TRUE)
        ->setSetting('jsonPath', ['grantsProfile', 'officialsArray'])
        ->setLabel('Officials');

      $info['addresses'] = ListDataDefinition::create('grants_profile_address')
//        ->setRequired(TRUE)
        ->setSetting('jsonPath', ['grantsProfile', 'addressesArray'])
        ->setLabel('Addresses');

      $info['bankAccounts'] = ListDataDefinition::create('grants_profile_bank_account')
//        ->setRequired(TRUE)
        ->setSetting('jsonPath', ['grantsProfile', 'bankAccountsArray'])
        ->setLabel('Bank Accounts');

    }
    return $this->propertyDefinitions;
  }

}

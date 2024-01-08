<?php

namespace Drupal\grants_profile\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Define address data.
 */
class GrantsProfileRegisteredCommunityDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['companyNameShort'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyNameShort',
        ]);

      $info['companyName'] = DataDefinition::create('string')
        ->setReadOnly(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyName',
        ]);

      $info['companyHome'] = DataDefinition::create('string')
        ->setReadOnly(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyHome',
        ]);

      $info['companyHomePage'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyHomePage',
        ]);

      $info['companyStatus'] = DataDefinition::create('string')
        ->setReadOnly(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyStatus',
        ]);

      $info['companyStatusSpecial'] = DataDefinition::create('string')
        ->setReadOnly(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'companyStatusSpecial',
        ]);

      $info['businessPurpose'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'businessPurpose',
        ]);

      $info['foundingYear'] = DataDefinition::create('string')
        ->setRequired(FALSE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'foundingYear',
        ]);

      $info['registrationDate'] = DataDefinition::create('string')
        ->setReadOnly(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'profileInfoArray',
          'registrationDate',
        ]);

      $info['officials'] = ListDataDefinition::create('grants_profile_application_official')
        ->setRequired(FALSE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'officialsArray',
        ]);

      $info['addresses'] = ListDataDefinition::create('grants_profile_address')
        ->setRequired(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'addressesArray',
        ]);

      $info['bankAccounts'] = ListDataDefinition::create('grants_profile_bank_account')
        ->setRequired(TRUE)
        ->setSetting('jsonPath', [
          'grantsProfile',
          'bankAccountsArray',
        ]);

    }
    return $this->propertyDefinitions;
  }

}

<?php

namespace Drupal\grants_premises\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Rented premise data.
 */
class GrantsRentedPremiseDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['premiseAddress'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'premiseAddress',
        ]);

      $info['premisePostalCode'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'premisePostalCode',
        ]);

      $info['premisePostOffice'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'premisePostOffice',
        ]);

      $info['rentSum'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'rentSum',
        ]);

      $info['usage'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'usage',
        ]);

      $info['daysPerWeek'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'daysPerWeek',
        ]);

      $info['hoursPerDay'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'hoursPerDay',
        ]);

      $info['lessorName'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'lessorName',
        ]);

      $info['lessorPhoneOrEmail'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'lessorPhoneOrEmail',
        ]);

      $info['lessorAddress'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'lessorAddress',
        ]);

      $info['lessorPostalCode'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'lessorPostalCode',
        ]);

      $info['lessorPostOffice'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'lessorPostOffice',
        ]);

      /* $info['dateBegin'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
      'dateBegin',
      ])
      ->setSetting('typeOverride', [
      'dataType' => 'string',
      'jsonType' => 'datetime',
      ]);

      $info['sum'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
      'sum',
      ])
      ->setSetting('typeOverride', [
      'dataType' => 'string',
      'jsonType' => 'double',
      ]);*/

    }
    return $this->propertyDefinitions;
  }

}

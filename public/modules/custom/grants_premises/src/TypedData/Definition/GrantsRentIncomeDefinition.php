<?php

namespace Drupal\grants_premises\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Rent income data.
 */
class GrantsRentIncomeDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['premiseName'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'premiseName',
        ]);

      $info['dateBegin'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'dateBegin',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'datetime',
        ]);

      $info['dateEnd'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'dateEnd',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'datetime',
        ]);

      $info['tenantName'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'tenantName',
        ]);

      $info['hours'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'hours',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

      $info['sum'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'sum',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

    }
    return $this->propertyDefinitions;
  }

}

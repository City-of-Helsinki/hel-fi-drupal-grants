<?php

namespace Drupal\grants_premises\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Rent cost data.
 */
class GrantsRentCostDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['rentCostsHours'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'rentCostsHours',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

      $info['rentCostsCost'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'rentCostsCost',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

      $info['rentCostsDifferenceToNextYear'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'rentCostsDifferenceToNextYear',
        ]);

    }
    return $this->propertyDefinitions;
  }

}

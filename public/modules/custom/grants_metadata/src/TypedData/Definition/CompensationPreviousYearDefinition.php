<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Data definition for compensations.
 */
class CompensationPreviousYearDefinition extends ComplexDataDefinitionBase {

  /**
   * Data definition for different subventions.
   *
   * @return array
   *   Property definitions.
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {

      $info = &$this->propertyDefinitions;

      $info['subventionType'] = DataDefinition::create('string')
        ->setLabel('subventionType')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'previousYearArray',
          'subventionType',
        ]);

      $info['amount'] = DataDefinition::create('float')
        ->setLabel('amount')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'previousYearArray',
          'amount',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'float',
        ])
        ->setSetting('defaultValue', 0)
        ->setRequired(TRUE)
        ->addConstraint('NotBlank');

      $info['usedAmount'] = DataDefinition::create('float')
        ->setLabel('usedAmount')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'previousYearArray',
          'amount',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'float',
        ])
        ->setSetting('defaultValue', 0)
        ->setRequired(TRUE)
        ->addConstraint('NotBlank');

    }

    // And here we will add later fields as well.
    return $this->propertyDefinitions;
  }

}

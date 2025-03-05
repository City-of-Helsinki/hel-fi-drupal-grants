<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Data definition for compensations.
 */
class CompensationTypeDefinition extends ComplexDataDefinitionBase {

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
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'compensationArray',
          'subventionType',
        ]);

      $info['amount'] = DataDefinition::create('float')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'compensationArray',
          'amount',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_metadata\ConvertHelper',
          'convertToFloat',
        ])
        ->setSetting('webformValueExtracter', [
          'service' => 'grants_metadata.converter',
          'method' => 'extractFloatValue',
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

<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Application official data.
 */
class OtherCompensationDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['issuer'] = DataDefinition::create('string')
        // ->setRequired(TRUE)
        ->setSetting('jsonPath', [
          'issuer',
        ]);
      // ->addConstraint('NotBlank')
      $info['issuerName'] = DataDefinition::create('string')
        // ->setRequired(TRUE)
        ->setSetting('jsonPath', [
          'issuerName',
        ]);
      // ->addConstraint('NotBlank')
      $info['year'] = DataDefinition::create('string')
        // ->setRequired(TRUE)
        ->setSetting('jsonPath', [
          'year',
        ]);
      // ->addConstraint('NotBlank')
      $info['amount'] = DataDefinition::create('float')
        ->setRequired(TRUE)
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'float',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_metadata\ConvertHelper',
          'convertToFloat',
        ])
        ->setSetting('webformValueExtracter', [
          'service' => 'grants_metadata.converter',
          'method' => 'extractFloatValue',
        ])
        ->setSetting('jsonPath', [
          'amount',
        ]);
      // ->addConstraint('NotBlank')
      $info['purpose'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setSetting('jsonPath', [
          'purpose',
        ]);
      // ->addConstraint('NotBlank')
    }
    return $this->propertyDefinitions;
  }

}

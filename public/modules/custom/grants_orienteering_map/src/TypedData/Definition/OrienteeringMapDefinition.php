<?php

namespace Drupal\grants_orienteering_map\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Orienteering Map data.
 */
class OrienteeringMapDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {

    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['mapName'] = DataDefinition::create('string')
        ->setLabel('Sijainnin nimi')
        ->setSetting('jsonPath', [
          'mapName',
        ]);

      $info['size'] = DataDefinition::create('string')
        ->setLabel('Sijainnin nimi')
        ->setSetting('jsonPath', [
          'size',
        ])
        ->setSetting('webformValueExtracter', [
          'service' => 'grants_metadata.converter',
          'method' => 'convertToCommaFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

      $info['voluntaryHours'] = DataDefinition::create('string')
        ->setLabel('Sijainnin nimi')
        ->setSetting('jsonPath', [
          'voluntaryHours',
        ])
        ->setSetting('webformValueExtracter', [
          'service' => 'grants_metadata.converter',
          'method' => 'convertToCommaFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['cost'] = DataDefinition::create('float')
        ->setLabel('Sijainnin nimi')
        ->setSetting('jsonPath', [
          'cost',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ])
        ->setSetting('webformValueExtracter', [
          'service' => 'grants_metadata.converter',
          'method' => 'convertToCommaFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

      $info['otherCompensations'] = DataDefinition::create('string')
        ->setLabel('Sijainnin nimi')
        ->setSetting('jsonPath', [
          'otherCompensations',
        ])
        ->setSetting('webformValueExtracter', [
          'service' => 'grants_metadata.converter',
          'method' => 'convertToCommaFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

    }
    return $this->propertyDefinitions;
  }

}

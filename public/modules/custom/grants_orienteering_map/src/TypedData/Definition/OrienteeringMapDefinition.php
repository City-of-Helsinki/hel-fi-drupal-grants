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
        ->setSetting('jsonPath', [
          'mapName',
        ]);

      $info['size'] = DataDefinition::create('float')
        ->setSetting('jsonPath', [
          'size',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

      $info['voluntaryHours'] = DataDefinition::create('float')
        ->setSetting('jsonPath', [
          'voluntaryHours',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'float',
        ]);

      $info['cost'] = DataDefinition::create('float')
        ->setSetting('jsonPath', [
          'cost',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

      $info['otherCompensations'] = DataDefinition::create('float')
        ->setSetting('jsonPath', [
          'otherCompensations',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

    }
    return $this->propertyDefinitions;
  }

}

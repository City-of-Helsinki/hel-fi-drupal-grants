<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Define Yleisavustushakemus data.
 */
class LiikuntaSuunnistusDefinition extends ComplexDataDefinitionBase {

  use ApplicationDefinitionTrait;

  /**
   * Base data definitions for all.
   *
   * @return array
   *   Property definitions.
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {

      $info = &$this->propertyDefinitions;

      foreach ($this->getBaseProperties() as $key => $property) {
        $info[$key] = $property;
      }

      $info['subventions']
        ->setSetting('defaultValue', [
          0 => [
            'amount' => '0',
            'subventionType' => '15',
          ],
        ]);

      $info['orienteering_maps'] = ListDataDefinition::create('grants_orienteering_map')
        ->setSetting('jsonPath', [
          'compensation',
          'orienteeringMapInfo',
          'orienteeringMapsArray',
        ]);
    }

    return $this->propertyDefinitions;
  }

  /**
   * Override property definition.
   *
   * @param string $name
   *   Property name.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface|void|null
   *   Property definition.
   */
  public function getPropertyDefinition($name) {
    $retval = parent::getPropertyDefinition($name);
    return $retval;
  }

}

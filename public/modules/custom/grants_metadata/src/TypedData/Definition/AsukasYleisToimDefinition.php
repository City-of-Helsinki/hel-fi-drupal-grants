<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Asukasosallisuus, yleis- ja toiminta-avustushakemus data.
 */
class AsukasYleisToimDefinition extends YleisDefinitionBase {

  /**
   * Base data definitions for all.
   *
   * @return array
   *   Property definitions.
   */
  public function getPropertyDefinitions(): array {
    $this->propertyDefinitions = parent::getPropertyDefinitions();
    $info = &$this->propertyDefinitions;

    // Remove the compensation_purpose from base class.
    unset($info['compensation_purpose']);

    // Add purpose with the same JSON path as compensation_purpose.
    $info['purpose'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'purpose',
      ]);

    return $this->propertyDefinitions;
  }

}

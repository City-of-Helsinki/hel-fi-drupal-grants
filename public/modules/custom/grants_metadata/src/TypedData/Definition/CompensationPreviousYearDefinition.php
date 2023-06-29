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
        ->setLabel('subventionType');

      $info['amount'] = DataDefinition::create('float')
        ->setLabel('amount')
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'float',
        ])
        ->setSetting('defaultValue', 0)
        ->setRequired(TRUE)
        ->addConstraint('NotBlank');

      $info['usedAmount'] = DataDefinition::create('float')
        ->setLabel('usedAmount')
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'float',
        ])
        ->setSetting('defaultValue', 0)
        ->setRequired(TRUE)
        ->addConstraint('NotBlank');

    }

    return $this->propertyDefinitions;
  }

}

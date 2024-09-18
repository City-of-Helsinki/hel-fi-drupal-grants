<?php

namespace Drupal\grants_test_base\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\grants_metadata\TypedData\Definition\ApplicationDefinitionTrait;

/**
 * Define TestDataDefinition data.
 */
class TestDataDefinition extends ComplexDataDefinitionBase {

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
    }
    return $this->propertyDefinitions;
  }

}

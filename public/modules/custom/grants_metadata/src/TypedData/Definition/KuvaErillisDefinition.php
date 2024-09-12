<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition;

/**
 * Define KuvaErillisDefinition data.
 */
class KuvaErillisDefinition extends ComplexDataDefinitionBase {

  use ApplicationDefinitionTrait;

  /**
   * @inheritDoc
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {

      $info = &$this->propertyDefinitions;

      foreach ($this->getBaseProperties() as $key => $property) {
        $info[$key] = $property;
      }
    }
    return $info;
  }

}

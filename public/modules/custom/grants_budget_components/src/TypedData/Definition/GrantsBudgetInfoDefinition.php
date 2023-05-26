<?php

namespace Drupal\grants_budget_components\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Define Budget Cost Static data.
 */
class GrantsBudgetInfoDefinition extends MapDataDefinition {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['budget_other_income'] = ListDataDefinition::create('grants_budget_income_other')
        ->setSetting('fullItemValueCallback', [
          'service' => 'grants_budget_components.service',
          'method' => 'processBudgetOtherValues',
        ])
        ->setSetting('webformDataExtracter', [
          'service' => 'grants_budget_components.service',
          'method' => 'extractToWebformData',
        ])
        ->setSetting('jsonPath', [
          'otherIncomeRowsArrayStatic',
        ]);

      $info['budget_other_cost'] = ListDataDefinition::create('grants_budget_cost_other')
        ->setSetting('fullItemValueCallback', [
          'service' => 'grants_budget_components.service',
          'method' => 'processBudgetOtherValues',
        ])
        ->setSetting('webformDataExtracter', [
          'service' => 'grants_budget_components.service',
          'method' => 'extractToWebformData',
        ])
        ->setSetting('jsonPath', [
          'otherCostRowsArrayStatic',
        ]);

      $info['costGroupName'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'costGroupName',
        ])
        ->setSetting('defaultValue', 'general')
        ->setSetting('fullItemValueCallback', [
          'service' => 'grants_budget_components.service',
          'method' => 'processGroupName',
        ]);

      $info['incomeGroupName'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'incomeGroupName',
        ])
        ->setSetting('defaultValue', 'general')
        ->setSetting('fullItemValueCallback', [
          'service' => 'grants_budget_components.service',
          'method' => 'processGroupName',
        ]);

    }

    return $this->propertyDefinitions;
  }

  /**
   * Sets the definition of a map property.
   *
   * @param string $name
   *   The name of the property to define.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|null $definition
   *   (optional) The property definition to set, or NULL to unset it.
   *
   * @return $this
   */
  public function setPropertyDefinition($name, DataDefinitionInterface $definition = NULL) {

    $this->getPropertyDefinitions();

    if (isset($definition)) {
      $this->propertyDefinitions[$name] = $definition;
    }
    else {
      unset($this->propertyDefinitions[$name]);
    }
    return $this;
  }

  /**
   * Helper function to get basic static income definition.
   *
   * @return \Drupal\Core\TypedData\ListDataDefinition
   *   Ready to use income static definition
   */
  public static function getStaticIncomeDefinition() {
    return ListDataDefinition::create('grants_budget_income_static')
      ->setSetting('fullItemValueCallback', [
        'service' => 'grants_budget_components.service',
        'method' => 'processBudgetStaticValues',
      ])
      ->setSetting('webformDataExtracter', [
        'service' => 'grants_budget_components.service',
        'method' => 'extractToWebformData',
      ])
      ->setSetting('jsonPath', [
        'incomeRowsArrayStatic',
      ]);
  }

  /**
   * Helper function to get basic static cost definition.
   *
   * @return \Drupal\Core\TypedData\ListDataDefinition
   *   Ready to use cost static definition
   */
  public static function getStaticCostDefinition() {
    return ListDataDefinition::create('grants_budget_cost_static')
      ->setSetting('fullItemValueCallback', [
        'service' => 'grants_budget_components.service',
        'method' => 'processBudgetStaticValues',
      ])
      ->setSetting('webformDataExtracter', [
        'service' => 'grants_budget_components.service',
        'method' => 'extractToWebformData',
      ])
      ->setSetting('jsonPath', [
        'costRowsArrayStatic',
      ]);
  }

}

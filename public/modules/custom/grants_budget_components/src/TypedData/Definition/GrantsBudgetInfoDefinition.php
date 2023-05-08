<?php

namespace Drupal\grants_budget_components\TypedData\Definition;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Define Budget Cost Static data.
 */
class GrantsBudgetInfoDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['budget_static_income'] = $this->getStaticIncomeDefinition();

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

      $info['toteutuneet_tulot_data'] = $this->getStaticIncomeDefinition();

      $info['budget_static_cost'] = $this->getStaticCostDefition();

      $info['toteutuneet_menot_data'] = $this->getStaticCostDefition();


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
   * @return ListDataDefinition
   */
  private function getStaticIncomeDefinition() {
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

  private function getStaticCostDefition() {
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

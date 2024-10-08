<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition;

/**
 * Define Kasko toiminta-avustus data.
 */
class KaskoToimintaDefinition extends ComplexDataDefinitionBase {

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

      $info['subventions'] = ListDataDefinition::create('grants_metadata_compensation_type')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'compensationArray',
        ]);

      $info['compensation_purpose'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'purpose',
        ]);

      $info['compensation_boolean'] = DataDefinition::create('boolean')
        ->setRequired(TRUE)
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ])
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'compensationPreviousYear',
        ]);

      $info['compensation_explanation'] = DataDefinition::create('string')
        ->setSetting('defaultValue', "")
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'explanation',
        ]);

      $info['toimintapaikka'] = ListDataDefinition::create('grants_place_of_operation')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
        ]);

      // 4 Talous.
      $info['budgetInfo'] = GrantsBudgetInfoDefinition::create('grants_budget_info')
        ->setSetting('propertyStructureCallback', [
          'service' => 'grants_budget_components.service',
          'method' => 'processBudgetInfo',
          'webform' => TRUE,
        ])
        ->setSetting('webformDataExtracter', [
          'service' => 'grants_budget_components.service',
          'method' => 'extractToWebformData',
          'mergeResults' => TRUE,
        ])
        ->setSetting('jsonPath', ['compensation', 'budgetInfo'])
        ->setPropertyDefinition(
          'tulot',
          GrantsBudgetInfoDefinition::getStaticIncomeDefinition()
            ->setSetting('fieldsForApplication', [
              'compensation',
              'customerFees',
              'donations',
            ]))
        ->setPropertyDefinition(
          'muut_avustukset_field',
          GrantsBudgetInfoDefinition::getOtherIncomeDefinition()
        )
        ->setPropertyDefinition(
          'henkilostomenot_ja_vuokrat',
          GrantsBudgetInfoDefinition::getStaticCostDefinition()
            ->setSetting('budgetGroupName', 'subventionUseCosts')
            ->setSetting('fieldsForApplication', [
              'salaries',
              'rentSum',
              'personnelSideCosts',
            ])
        )
        ->setPropertyDefinition(
          'muut_menot_4',
          GrantsBudgetInfoDefinition::getOtherCostDefinition()
            ->setSetting('budgetGroupName', 'subventionUseCosts')
        )
        ->setPropertyDefinition(
          'avustuksen_kaytto_palveluiden_ostot_eriteltyina_2',
          GrantsBudgetInfoDefinition::getStaticCostDefinition()
            ->setSetting('budgetGroupName', 'costsForServicesAcquired')
            ->setSetting('fieldsForApplication', [
              'snacks',
              'cleaning',
              'premisesService',
              'travelCosts',
            ])
        )
        ->setPropertyDefinition(
          'muut_palveluiden_ostot_2',
          GrantsBudgetInfoDefinition::getOtherCostDefinition()
            ->setSetting('budgetGroupName', 'costsForServicesAcquired')
        )
        ->setPropertyDefinition(
          'muut_aineet_tarvikkeet_ja_tavarat_2',
          GrantsBudgetInfoDefinition::getStaticCostDefinition()
            ->setSetting('budgetGroupName', 'costsForMaterialsSuppliesAndGoods')
            ->setSetting('fieldsForApplication', [
              'snacks',
              'heating',
              'water',
              'electricity',
              'supplies',
            ])
        )
        ->setPropertyDefinition(
          'muut_menot_tarvikkeet',
          GrantsBudgetInfoDefinition::getOtherCostDefinition()
            ->setSetting('budgetGroupName', 'costsForMaterialsSuppliesAndGoods')
        )
        ->setPropertyDefinition(
          'avustuksen_kaytto_muut_kulut_eriteltyina_2',
          GrantsBudgetInfoDefinition::getStaticCostDefinition()
            ->setSetting('budgetGroupName', 'otherCosts')
            ->setSetting('fieldsForApplication', [
              'admin',
              'accounting',
              'health',
            ])
        )
        ->setPropertyDefinition(
          'muut_menot_2',
          GrantsBudgetInfoDefinition::getOtherCostDefinition()
            ->setSetting('budgetGroupName', 'otherCosts')
        )
        ->setPropertyDefinition(
          'asiakasmaksutulojen_kaytto_ja_mahdolliset_lahjoitukset_2',
          GrantsBudgetInfoDefinition::getStaticCostDefinition()
            ->setSetting('budgetGroupName', 'useOfCustomerFeeIncome')
            ->setSetting('fieldsForApplication', [
              'salaries',
              'personnelSideCosts',
              'rentSum',
              'materials',
              'services',
            ])
        )
        ->setPropertyDefinition(
          'muut_menot_3',
          GrantsBudgetInfoDefinition::getOtherCostDefinition()
            ->setSetting('budgetGroupName', 'useOfCustomerFeeIncome')
        )
        // Remove default "other" budget components,
        // as this form has 6 differently named ones.
        ->setPropertyDefinition('budget_other_income')
        ->setPropertyDefinition('budget_other_cost');
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

<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition;

/**
 * Define Yleisavustushakemus data.
 */
class NuorisoProjektiDefinition extends ComplexDataDefinitionBase {

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
        ->setLabel('compensationArray')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'compensationArray',
        ])
        ->addConstraint('NotBlank')
        ->setRequired(TRUE)
        ->setSetting('formSettings', [
          'formElement' => 'subventions',
        ]);
      $info['kenelle_haen_avustusta'] = DataDefinition::create('string')
        ->setLabel('Avustuksen kohde')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'targetOfSubvention',
        ]);

      $info['jasenet_7_28'] = DataDefinition::create('integer')
        ->setLabel('Kuinka monta 7-28 –vuotiasta helsinkiläistä jäsentä tai osallistujaa nuorten toimintaryhmässä/yhdistyksessä/talokerhossa on?')
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'membersAge7to28YearsLocal',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['jasenet_kaikki'] = DataDefinition::create('integer')
        ->setLabel('Kuinka monta jäsentä tai osallistujaa ryhmässä/yhdistyksessä/talokerhossa on yhteensä?')
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'membersSummaryGlobal',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['projektin_nimi'] = DataDefinition::create('string')
        ->setLabel('Nimi')
        ->setSetting('jsonPath', [
          'compensation',
          'projectInfoArray',
          'projectName',
        ]);

      $info['projektin_tavoitteet'] = DataDefinition::create('string')
        ->setLabel('Tavoitteet')
        ->setSetting('jsonPath', [
          'compensation',
          'projectInfoArray',
          'goal',
        ]);

      $info['projektin_sisalto'] = DataDefinition::create('string')
        ->setLabel('Sisältö')
        ->setSetting('jsonPath', [
          'compensation',
          'projectInfoArray',
          'content',
        ]);

      $info['projekti_alkaa'] = DataDefinition::create('string')
        ->setLabel('Alkamispäivä')
        ->setSetting('jsonPath', [
          'compensation',
          'projectInfoArray',
          'startDate',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'datetime',
        ])
        ->setSetting('valueCallback', [
          'service' => 'grants_metadata.converter',
          'method' => 'convertDates',
          'arguments' => [
            'dateFormat' => 'Y-m-d',
          ],
        ]);

      $info['projekti_loppuu'] = DataDefinition::create('string')
        ->setLabel('Päättymispäivä')
        ->setSetting('jsonPath', [
          'compensation',
          'projectInfoArray',
          'endDate',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'datetime',
        ])
        ->setSetting('valueCallback', [
          'service' => 'grants_metadata.converter',
          'method' => 'convertDates',
          'arguments' => [
            'dateFormat' => 'Y-m-d',
          ],
        ]);

      $info['osallistujat_7_28'] = DataDefinition::create('integer')
        ->setLabel('Helsinkiläiset 7-28-vuotiaat osallistujat')
        ->setSetting('jsonPath', [
          'compensation',
          'projectInfoArray',
          'age7to28yearsLocal',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['osallistujat_kaikki'] = DataDefinition::create('integer')
        ->setLabel('Kaikki osallistujat')
        ->setSetting('jsonPath', [
          'compensation',
          'projectInfoArray',
          'all',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      // @todo check with finnish speaker if this is mapped correctly (osallistujat and jasenet) as it might be inversed.
      $info['projektin_paikka_2'] = DataDefinition::create('string')
        ->setLabel('Paikka')
        ->setSetting('jsonPath', [
          'compensation',
          'projectInfoArray',
          'location',
        ]);

      $info['lisakysymys_2'] = DataDefinition::create('string')
        ->setLabel('Kysymyksen otsikko näkyy hakemuksella')
        ->setSetting('jsonPath', [
          'compensation',
          'projectInfoArray',
          'extraQuestion',
        ]);
      // @todo if empty this should not be displayed.
      $info['omarahoitusosuuden_kuvaus'] = DataDefinition::create('string')
        ->setLabel('Omarahoitusosuuden kuvaus')
        ->setSetting('jsonPath', [
          'compensation',
          'budgetInfo',
          'budgetInfoArray',
          'selfFinancingDescription',
        ]);

      $info['omarahoitusosuus'] = DataDefinition::create('integer')
        ->setLabel('Euroa')
        ->setSetting('jsonPath', [
          'compensation',
          'budgetInfo',
          'budgetInfoArray',
          'selfFinancingAmount',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

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
          'budget_other_income',
          GrantsBudgetInfoDefinition::getOtherIncomeDefinition()
        )
        ->setPropertyDefinition(
          'budget_other_cost',
          GrantsBudgetInfoDefinition::getOtherCostDefinition()
        );
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

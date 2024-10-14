<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition;

/**
 * Define KuvaErillisDefinition data.
 */
class KuvaErillisDefinition extends ComplexDataDefinitionBase {

  use ApplicationDefinitionTrait;

  /**
   * {@inheritDoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {

      $info = &$this->propertyDefinitions;

      foreach ($this->getBaseProperties() as $key => $property) {
        $info[$key] = $property;
      }
    }

    $info['compensation_purpose'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'purpose',
      ]);

    $info['hankesuunnitelma_jatkohakemus'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankesuunnitelma_jatkohakemus',
      ]);

    $info['hankkeen_tarkoitus_tavoitteet'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_tarkoitus_tavoitteet',
      ]);

    $info['hankkeen_toimenpiteet_aikataulu'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_toimenpiteet_aikataulu',
      ]);

    $info['hankkeen_toimenpiteet_alkupvm'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_toimenpiteet_alkupvm',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'datetime',
      ])
      ->setSetting('valueCallback', [
        'service' => 'grants_metadata.converter',
        'method' => 'convertDates',
        'arguments' => [
          'dateFormat' => 'c',
        ],
      ]);

    $info['hankkeen_toimenpiteet_loppupvm'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_toimenpiteet_loppupvm',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'datetime',
      ])
      ->setSetting('valueCallback', [
        'service' => 'grants_metadata.converter',
        'method' => 'convertDates',
        'arguments' => [
          'dateFormat' => 'c',
        ],
      ]);

    $info['hankkeen_keskeisimmat_kumppanit'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_keskeisimmat_kumppanit',
      ]);

    $info['haun_painopisteet_liikkumis_kehitys'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'haun_painopisteet_liikkumis_kehitys',
      ]);

    $info['haun_painopisteet_digi_kehitys'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'haun_painopisteet_digi_kehitys',
      ]);

    $info['haun_painopisteet_vertais_kehitys'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'haun_painopisteet_vertais_kehitys',
      ]);

    $info['haun_painopisteet_kulttuuri_kehitys'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'haun_painopisteet_kulttuuri_kehitys',
      ]);

    $info['hankkeen_kohderyhmat_kenelle'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_kohderyhmat_kenelle',
      ]);

    $info['hankkeen_kohderyhmat_erityisryhmat'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_kohderyhmat_erityisryhmat',
      ]);

    $info['hankkeen_kohderyhmat_tavoitus'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_kohderyhmat_tavoitus',
      ]);

    $info['hankkeen_kohderyhmat_konkretia'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_kohderyhmat_konkretia',
      ]);

    $info['hankkeen_kohderyhmat_osallisuus'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_kohderyhmat_osallisuus',
      ]);

    $info['hankkeen_kohderyhmat_osaaminen'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_kohderyhmat_osaaminen',
      ]);

    $info['hankkeen_kohderyhmat_postinrot'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_kohderyhmat_postinrot',
      ]);

    $info['hankkeen_kohderyhmat_miksi_alue'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_kohderyhmat_miksi_alue',
      ]);

    $info['hankkeen_riskit_keskeisimmat'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_riskit_keskeisimmat',
      ]);

    $info['hankkeen_riskit_seuranta'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_riskit_seuranta',
      ]);

    $info['hankkeen_riskit_vakiinnuttaminen'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        'hankkeen_riskit_vakiinnuttaminen',
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
        'tulot',
        GrantsBudgetInfoDefinition::getStaticIncomeDefinition()
          ->setSetting('fieldsForApplication', [
            'compensation',
          ])
      )
      ->setPropertyDefinition(
        'talous_tulon_tyyppi',
        GrantsBudgetInfoDefinition::getOtherIncomeDefinition()
      )
      ->setPropertyDefinition(
        'talous_menon_tyyppi',
        GrantsBudgetInfoDefinition::getOtherCostDefinition()
      );

    $info['additional_information'] = DataDefinition::create('string')
      ->setSetting('jsonPath', ['compensation', 'additionalInformation'])
      ->setSetting('defaultValue', "");

    return $this->propertyDefinitions;
  }

}

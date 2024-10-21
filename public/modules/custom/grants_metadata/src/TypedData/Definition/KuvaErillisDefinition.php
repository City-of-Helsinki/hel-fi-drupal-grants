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

    $customQuestions = [
      'hankesuunnitelma_jatkohakemus' => [],
      'hankkeen_tarkoitus_tavoitteet' => [],
      'hankkeen_toimenpiteet_aikataulu' => [],
      'hankkeen_toimenpiteet_alkupvm' => [
        'defaultValue' => '',
        'typeOverride' => [
          'dataType' => 'string',
          'jsonType' => 'datetime',
        ],
        'valueCallback' => [
          'service' => 'grants_metadata.converter',
          'method' => 'convertDates',
          'arguments' => [
            'dateFormat' => 'c',
          ],
        ],
      ],
      'hankkeen_toimenpiteet_loppupvm' => [
        'defaultValue' => '',
        'typeOverride' => [
          'dataType' => 'string',
          'jsonType' => 'datetime',
        ],
        'valueCallback' => [
          'service' => 'grants_metadata.converter',
          'method' => 'convertDates',
          'arguments' => [
            'dateFormat' => 'c',
          ],
        ],
      ],
      'hankkeen_keskeisimmat_kumppanit' => [],
      'haun_painopisteet_liikkumis_kehitys' => [],
      'haun_painopisteet_digi_kehitys' => [],
      'haun_painopisteet_vertais_kehitys' => [],
      'haun_painopisteet_kulttuuri_kehitys' => [],
      'hankkeen_kohderyhmat_kenelle' => [],
      'hankkeen_kohderyhmat_erityisryhmat' => [],
      'hankkeen_kohderyhmat_tavoitus' => [],
      'hankkeen_kohderyhmat_konkretia' => [],
      'hankkeen_kohderyhmat_osallisuus' => [],
      'hankkeen_kohderyhmat_osaaminen' => [],
      'hankkeen_kohderyhmat_postinrot' => [],
      'hankkeen_kohderyhmat_miksi_alue' => [],
      'hankkeen_riskit_keskeisimmat' => [],
      'hankkeen_riskit_seuranta' => [],
      'hankkeen_riskit_vakiinnuttaminen' => [],
      // example forms
      'puuttuva_kentta_1' => [],
      'puuttuva_kentta_2' => [],
      'vaikka_sahkoposti' => [],
      'checkboksiki_onnistuu' => [],
      'tekstialue' => [],
      'tekstikentta' => [],
      'paivamaara' => [],
    ];

    foreach ($customQuestions as $key => $value) {
      $this->createCustomQuestionDefinitions($key, $value,$info);
    }

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

  /**
   * Helper function generate repetitive definition.
   *
   * @param string $key
   *   Webform element key.
   * @param array $value
   *  Additional settings for given field.
   * @param array $info
   *   Data definitions.
   */
  private function createCustomQuestionDefinitions(string $key, array $value, array &$info): void {
    // Create initial definition with position in JSON.
    $info[$key] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        $key,
      ]);
    // Add type override if set.
    if(isset($value['typeOverride'])) {
      $info[$key]->setSetting('typeOverride', $value['typeOverride']);
    }
    // Add value callback if set.
    if(isset($value['valueCallback'])) {
      $info[$key]->setSetting('valueCallback', $value['valueCallback']);
    }
    // Add default value if set or empty value.
    if (isset($value['defaultValue'])) {
      $info[$key]->setSetting('defaultValue', $value['defaultValue']);
    }
    // DO not add defaultValue if not set, this makes all fields inserted into
    // data, this is not an issue, but if there's lot of fields data may get confusing.

    // The negative is that if field is not set required in form, it will not
    // be added to data. If an empty field & value is wanted, the defaultValue
    // can be added to specific field in array above.
  }

}

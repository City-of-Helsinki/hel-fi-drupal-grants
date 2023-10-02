<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Define Yleisavustushakemus data.
 */
class LiikuntaTilankayttoDefinition extends ComplexDataDefinitionBase {

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

      // Section 2: Avustustiedot.
      $info['subventions'] = ListDataDefinition::create('grants_metadata_compensation_type')
        ->setLabel('compensationArray')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'compensationArray',
        ]);

      $info['compensation_purpose'] = DataDefinition::create('string')
        ->setLabel('')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'purpose',
        ]);

      $info['compensation_explanation'] = DataDefinition::create('string')
        ->setLabel('compensationInfo=>explanation')
        ->setSetting('defaultValue', "")
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'explanation',
        ])
        ->setSetting('webformDataExtracter', [
          'service' => 'grants_metadata.atv_schema',
          'method' => 'returnRelations',
          'mergeResults' => TRUE,
          'arguments' => [
            'relations' => [
              'master' => 'compensation_explanation',
              'slave' => 'compensation_boolean',
              'type' => 'boolean',
            ],
          ],
        ]);

      $info['liikuntatiloista_maksetut_vuokrat'] = ListDataDefinition::create('grants_rent_cost')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'premisesCompensation',
          'rentCostsArray',
        ]);

      $info['seuran_yhdistyksen_saamat_vuokrat_edellisen_kalenterivuoden_ajal'] = ListDataDefinition::create('grants_rent_income')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'premisesCompensation',
          'rentIncomesArray',
        ]);

      // Section 3: Yhteisön toiminta. Empty values not impl. in avus2 yet.
      $mappings = [
        'miehet_20_63_vuotiaat_' => 'menGlobal',
        'joista_helsinkilaisia_miehet_20_63' => 'menLocal',
        'naiset_20_63_vuotiaat_' => 'womenGlobal',
        'joista_helsinkilaisia_naiset_20_63' => 'womenLocal',
        'muut_20_63_vuotiaat_' => 'adultOthersGlobal',
        'joista_helsinkilaisia_muut_20_63' => 'adultOthersLocal',
        'miehet_64' => 'seniorMenGlobal',
        'joista_helsinkilaisia_miehet_64' => 'seniorMenLocal',
        'naiset_64' => 'seniorWomenGlobal',
        'joista_helsinkilaisia_naiset_64' => 'seniorWomenLocal',
        'muut_64' => 'seniorOthersGlobal',
        'joista_helsinkilaisia_muut_64' => 'seniorOthersLocal',
        'pojat_20' => 'boysGlobal',
        'joista_helsinkilaisia_pojat_20' => 'boysLocal',
        'tytot_20' => 'girlsGlobal',
        'joista_helsinkilaisia_tytot_20' => 'girlsLocal',
        'muut_20' => 'juniorOthersGlobal',
        'joista_helsinkilaisia_muut_20' => 'juniorOthersLocal',
        'miehet_20_63_vuotiaat_aktiiviharrastajat' => 'activeFanciersMenGlobal',
        'joista_helsinkilaisia_miehet_20_63_aktiiviharrastajat' => 'activeFanciersMenLocal',
        'naiset_20_63_vuotiaat_aktiiviharrastajat' => 'activeFanciersWomenGlobal',
        'joista_helsinkilaisia_naiset_20_63_aktiiviharrastajat' => 'activeFanciersWomenLocal',
        'muut_20_63_vuotiaat_aktiiviharrastajat' => 'activeFanciersAdultOthersGlobal',
        'joista_helsinkilaisia_muut_20_63_aktiiviharrastajat' => 'activeFanciersAdultOthersLocal',
        'miehet_64_aktiiviharrastajat' => 'activeFanciersSeniorMenGlobal',
        'joista_helsinkilaisia_miehet_64_aktiiviharrastajat' => 'activeFanciersSeniorMenLocal',
        'naiset_64_aktiiviharrastajat' => 'activeFanciersSeniorWomenGlobal',
        'joista_helsinkilaisia_naiset_64_aktiiviharrastajat' => 'activeFanciersSeniorWomenLocal',
        'muut_64_aktiiviharrastajat' => 'activeFanciersSeniorOthersGlobal',
        'joista_helsinkilaisia_muut_64_aktiiviharrastajat' => 'activeFanciersSeniorOthersLocal',
        'pojat_20_aktiiviharrastajat' => 'activeFanciersBoysGlobal',
        'joista_helsinkilaisia_pojat_20_aktiiviharrastajat' => 'activeFanciersBoysLocal',
        'tytot_20_aktiiviharrastajat' => 'activeFanciersGirlsLocal',
        'joista_helsinkilaisia_tytot_20_aktiiviharrastajat' => 'activeFanciersGirlsLocal',
        'muut_20_aktiiviharrastajat' => 'activeFanciersJuniorOthersGlobal',
        'joista_helsinkilaisia_muut_20_aktiiviharrastajat' => 'activeFanciersJuniorOthersLocal',
        'valmentajien_ohjaajien_maara_edellisena_vuonna_yhteensa' => 'allCoaches',
        'joista_valmentaja_ja_ohjaajakoulutuksen_vok_1_5_tason_koulutukse' => 'level1to5Coaches',
      ];

      foreach ($mappings as $key => $value) {
        if (empty($value)) {
          // Not yet implemented in avus2.
          continue;
        }
        $this->createRepeatedMembershipDefinitions($key, $value, $info);
      }

      $info['club_section'] = ListDataDefinition::create('grants_club_section')
        ->setSetting('jsonPath', [
          'compensation',
          'membersInfo',
          'clubSectionsArray',
        ]);

    }
    return $this->propertyDefinitions;
  }

  /**
   * Helper function generate repetitive definition.
   *
   * @param string $key
   *   Webform element key.
   * @param string $jsonPath
   *   Last part of the JSON path.
   * @param array $info
   *   Data definitions.
   */
  private function createRepeatedMembershipDefinitions($key, $jsonPath, &$info) {
    $info[$key] = DataDefinition::create('integer')
      ->setSetting('jsonPath', [
        'compensation',
        'membersInfo',
        'membersInfoArray',
        $jsonPath,
      ])->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToInt',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'int',
      ]);
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

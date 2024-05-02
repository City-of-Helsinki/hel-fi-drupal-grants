<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition;

/**
 * Define Kulttuurin kehittämisavustukset data.
 */
class KuvaKehaDefinition extends ComplexDataDefinitionBase {

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

      $info['ensisijainen_taiteen_ala'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'primaryArt',
        ]);

      $info['hankkeen_nimi'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'nameOfEvent',
        ]);

      $info['hankkeen_tai_toiminnan_lyhyt_esittelyteksti'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'purpose',
        ]);

      $info['kokoaikainen_henkilosto'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'communityInfo',
          'generalCommunityInfoArray',
          'staffPeopleFulltime',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['osa_aikainen_henkilosto'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'communityInfo',
          'generalCommunityInfoArray',
          'staffPeopleParttime',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['vapaaehtoinen_henkilosto'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'communityInfo',
          'generalCommunityInfoArray',
          'staffPeopleVoluntary',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['kokoaikainen_henkilotyovuosia'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'communityInfo',
          'generalCommunityInfoArray',
          'staffManyearsFulltime',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ])
        ->setSetting('webformValueExtracter', [
          'service' => 'grants_metadata.converter',
          'method' => 'extractFloatValue',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

      $info['osa_aikainen_henkilotyovuosia'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'communityInfo',
          'generalCommunityInfoArray',
          'staffManyearsParttime',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ])
        ->setSetting('webformValueExtracter', [
          'service' => 'grants_metadata.converter',
          'method' => 'extractFloatValue',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

      $info['toiminta_taiteelliset_lahtokohdat'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_taiteelliset_lahtokohdat',
        ]);

      $info['toiminta_tasa_arvo'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_tasa_arvo',
        ]);

      $info['toiminta_saavutettavuus'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_saavutettavuus',
        ]);

      $info['toiminta_yhteisollisyys'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_yhteisollisyys',
        ]);

      $info['toiminta_kohderyhmat'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_kohderyhmat',
        ]);

      $info['toiminta_ammattimaisuus'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_ammattimaisuus',
        ]);

      $info['toiminta_ekologisuus'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_ekologisuus',
        ]);

      $info['toiminta_yhteistyokumppanit'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_yhteistyokumppanit',
        ]);

      $info['hanke_alkaa'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'projectStartDate',
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
      $info['hanke_loppuu'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'projectEndDate',
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

      $info['festivaalin_tai_tapahtuman_paivamaarat'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'eventOrFestivalDates',
        ]);
      $info['laajempi_hankekuvaus'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'detailedProjectDescription',
        ]);

      $info['tila'] = ListDataDefinition::create('grants_premises')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedPremisesArray',
        ])
        ->setSetting('fullItemValueCallback', [
          'service' => 'grants_premises.service',
          'method' => 'processPremises',
          'webform' => TRUE,
        ])
        ->setSetting('webformDataExtracter', [
          'service' => 'grants_premises.service',
          'method' => 'extractToWebformData',
        ])
        ->setSetting('fieldsForApplication', [
          'premiseName',
          'isOwnedByCity',
          'postCode',
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
          'budget_static_income',
          GrantsBudgetInfoDefinition::getStaticIncomeDefinition()
            ->setSetting('fieldsForApplication', [
              'compensation',
              'sponsorships',
              'entryFees',
              'sales',
              'ownFunding',
              'plannedOtherCompensations',
            ])
          );

      $info['sisaltyyko_toiminnan_toteuttamiseen_jotain_muuta_rahanarvoista_p'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'budgetInfo',
          'budgetInfoArray',
          'otherValuables',
        ]);

      $info['organisaatio_kuuluu_valtionosuusjarjestelmaan_vos_'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'compensation',
          'budgetInfo',
          'budgetInfoArray',
          'isPartOfVOS',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['kyseessa_on_festivaali_tai_tapahtuma'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'isFestival',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);
    }

    $info['vuodet_joille_monivuotista_avustusta_on_haettu_tai_myonetty'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'yearsForMultiYearApplication',
      ])
      ->setSetting('webformDataExtracter', [
        'service' => 'grants_metadata.atv_schema',
        'method' => 'returnRelations',
        'mergeResults' => TRUE,
        'arguments' => [
          'relations' => [
            'slave' => 'kyseessa_on_monivuotinen_avustus',
            'master' => 'vuodet_joille_monivuotista_avustusta_on_haettu_tai_myonetty',
            'type' => 'boolean',
          ],
        ],
      ]);

    $info['erittely_kullekin_vuodelle_haettavasta_avustussummasta'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'breakdownOfYearlySums',
      ]);

    $info['members_applicant_person_global'] = DataDefinition::create('integer')
      ->setSetting('jsonPath', [
        'compensation',
        'communityInfo',
        'generalCommunityInfoArray',
        'membersPersonGlobal',
      ])->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToInt',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'int',
      ]);

    $info['members_applicant_person_local'] = DataDefinition::create('integer')
      ->setSetting('jsonPath', [
        'compensation',
        'communityInfo',
        'generalCommunityInfoArray',
        'membersPersonLocal',
      ])->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToInt',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'int',
      ]);

    $info['members_applicant_community_global'] = DataDefinition::create('integer')
      ->setSetting('jsonPath', [
        'compensation',
        'communityInfo',
        'generalCommunityInfoArray',
        'membersCommunityGlobal',
      ])->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToInt',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'int',
      ]);

    $info['members_applicant_community_local'] = DataDefinition::create('integer')
      ->setSetting('jsonPath', [
        'compensation',
        'communityInfo',
        'generalCommunityInfoArray',
        'membersCommunityLocal',
      ])->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToInt',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'int',
      ]);

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

<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition;

/**
 * Define Yleisavustushakemus data.
 */
class KuvaToimintaDefinition extends ComplexDataDefinitionBase {

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

      $info['tulevat_vuodet_joiden_ajalle_monivuotista_avustusta_on_haettu_ta'] = DataDefinition::create('string')
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
              'master' => 'tulevat_vuodet_joiden_ajalle_monivuotista_avustusta_on_haettu_ta',
              'type' => 'boolean',
            ],
          ],
        ]);

      $info['erittely_kullekin_vuodelle_haettavasta_avustussummasta_'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'breakdownOfYearlySums',
        ]);

      $info['ensisijainen_taiteen_ala'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'primaryArt',
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

      $info['taiteellisen_toiminnan_tilaa_omistuksessa_tai_ymparivuotisesti_p'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'compensation',
          'communityInfo',
          'generalCommunityInfoArray',
          'isOwnerOrPrimaryTenantOfArtpremises',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['tila'] = ListDataDefinition::create('grants_premises')
        ->setSetting('jsonPath', [
          'compensation',
          'communityInfo',
          'artPremisesArray',
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
          'premiseType',
          'isOthersUse',
          'premiseName',
          'isOwnedByApplicant',
          'postCode',
          'isOwnedByCity',
        ]);

      $info['tapahtuma_tai_esityspaivien_maara_helsingissa'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'eventDaysCountHki',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['kantaesitysten_maara'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'firstPublicPerformancesCount',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['ensi_iltojen_maara_helsingissa'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'premiereCountHki',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['festivaalin_tai_tapahtuman_kohdalla_tapahtuman_paivamaarat'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'eventOrFestivalDates',
        ]);

      $info['muut_keskeiset_toimintamuodot'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'otherKeyActivities',
        ]);

      /* Esitykset */
      $info['esitykset_maara_helsingissa'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'performanceCountHki',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['esitykset_maara_kaikkiaan'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'performanceCountAll',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      /* Näyttelyt */
      $info['nayttelyt_maara_helsingissa'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'exhibitionCountHki',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['nayttelyt_maara_kaikkiaan'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'exhibitionCountAll',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      /* Työpajat */
      $info['tyopaja_maara_helsingissa'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'workshopCountHki',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['tyopaja_maara_kaikkiaan'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'workshopCountAll',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      /*
       *
       * Toteutuneet määrät.
       *
       *
       */

      $info['tapahtuma_tai_esityspaivien_maara_helsingissa_toteutuneet'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'eventDaysCount',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['oliko_kyseessa_festivaali_tai_tapahtuma_'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'isEventOrFestival',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      /* Esitykset */
      $info['esitykset_maara_kaikkiaan_toteutuneet'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'performanceCountAll',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['esitykset_maara_helsingissa_toteutuneet'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'performanceCountHki',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      /* Näyttelyt */
      $info['nayttelyt_maara_helsingissa_toteutuneet'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'exhibitionCountHki',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['nayttelyt_maara_kaikkiaan_toteutuneet'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'exhibitionCountAll',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      /* Työpajat */
      $info['tyopaja_maara_helsingissa_toteutuneet'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'workshopCountHki',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['tyopaja_maara_kaikkiaan_toteutuneet'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'workshopCountAll',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['toteutuneet_kantaesitysten_maara'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'firstPublicPerformancesCount',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['toteutuneet_ensi_iltojen_maara_helsingissa'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'premiereCountHki',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['toteutuneet_tila'] = ListDataDefinition::create('grants_premises')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedPremisesArray',
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
          'postCode',
          'isOwnedByCity',
        ]);

      $info['maara_helsingissa_toteutuneet'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'eventsVisitorsHkiTotal',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['maara_kaikkiaan_toteutuneet'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'eventsVisitorsTotal',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      /* Toiminnan lähtökohdat */
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

      $info['toiminta_tavoitteet'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_tavoitteet',
        ]);

      $info['toiminta_kaytetyt_keinot'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_kaytetyt_keinot',
        ]);

      $info['toiminta_tulevat_muutokset'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_tulevat_muutokset',
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
            ->setSetting('fieldsForApplication', ['compensation',
              'plannedStateOperativeSubvention',
              'plannedOtherCompensations',
              'sponsorships',
              'entryFees',
              'sales',
              'financialFundingAndInterests',
            ])
        )
        ->setPropertyDefinition(
          'menot_yhteensa',
          GrantsBudgetInfoDefinition::getStaticCostDefinition()
            ->setSetting('fieldsForApplication', ['totalCosts',
            ])
        )
        ->setPropertyDefinition(
          'suunnitellut_menot',
          GrantsBudgetInfoDefinition::getStaticCostDefinition()
            ->setSetting('fieldsForApplication', [
              'plannedTotalCosts',
            ])
        )
        ->setPropertyDefinition(
          'toteutuneet_tulot_data',
          GrantsBudgetInfoDefinition::getStaticIncomeDefinition()
            ->setSetting('fieldsForApplication', [
              "otherCompensationFromCity",
              "stateOperativeSubvention",
              "otherCompensations",
              "totalIncome",
            ])
        );

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

      $info['kokoaikainen_henkilotyovuosia'] = DataDefinition::create('float')
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

      $info['osa_aikainen_henkilotyovuosia'] = DataDefinition::create('float')
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

      $info['organisaatio_kuului_valtionosuusjarjestelmaan_vos_'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'compensation',
          'budgetInfo',
          'budgetInfoArray',
          'wasPartOfVOS',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['sisaltyyko_toiminnan_toteuttamiseen_jotain_muuta_rahanarvoista_p'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'budgetInfo',
          'budgetInfoArray',
          'otherValuables',
        ]);
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

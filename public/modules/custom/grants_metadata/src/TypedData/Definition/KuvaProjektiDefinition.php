<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition;
use Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler;

/**
 * Define Yleisavustushakemus data.
 */
class KuvaProjektiDefinition extends ComplexDataDefinitionBase {

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
        ])
        ->addConstraint('NotBlank')
        ->setRequired(TRUE)
        ->setSetting('formSettings', [
          'formElement' => 'ensisijainen_taiteen_ala',
        ]);

      $info['hankkeen_nimi'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'nameOfEvent',
        ])
        ->addConstraint('NotBlank')
        ->setRequired(TRUE)
        ->setSetting('formSettings', [
          'formElement' => 'hankkeen_nimi',
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
        ->setSetting('formSettings', [
          'formElement' => 'osa_aikainen_henkilosto',
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
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ])
        ->setSetting('formSettings', [
          'formElement' => 'osa_aikainen_henkilosto',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
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

      $info['toiminta_taiteelliset_lahtokohdat'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('addConditionally', [
          'class' => self::class,
          'method' => 'activityConditionCheck',
        ])
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_taiteelliset_lahtokohdat',
        ]);

      $info['toiminta_tasa_arvo'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('addConditionally', [
          'class' => self::class,
          'method' => 'activityConditionCheck',
        ])
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_tasa_arvo',
        ]);

      $info['toiminta_saavutettavuus'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('addConditionally', [
          'class' => self::class,
          'method' => 'activityConditionCheck',
        ])
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_saavutettavuus',
        ]);

      $info['toiminta_yhteisollisyys'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('addConditionally', [
          'class' => self::class,
          'method' => 'activityConditionCheck',
        ])
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
        ->setSetting('addConditionally', [
          'class' => self::class,
          'method' => 'activityConditionCheck',
        ])
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_ammattimaisuus',
        ]);

      $info['toiminta_ekologisuus'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('addConditionally', [
          'class' => self::class,
          'method' => 'activityConditionCheck',
        ])
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

      $info['ensimmainen_yleisolle_avoimen_tilaisuuden_paikka_helsingissa'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'firstPublicEventLocationHki',
        ]);

      $info['postinumero'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'firstPublicEventLocationPostCode',
        ]);

      $info['kyseessa_on_kaupungin_omistama_tila'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'isOwnedByCity',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['maara_helsingissa'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
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

      $info['maara_kaikkiaan'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
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

      $info['ensimmaisen_yleisolle_avoimen_tilaisuuden_paivamaara'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'plannedActivityInfoArray',
          'firstPublicOccasionDate',
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

      $info['festivaalin_tai_tapahtuman_kohdalla_tapahtuman_paivamaarat'] = DataDefinition::create('string')
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
              'plannedOtherCompensations',
              'sponsorships',
              'entryFees',
              'sales',
              'ownFunding',
            ])
        )
        ->setPropertyDefinition(
          'budget_static_cost',
          GrantsBudgetInfoDefinition::getStaticCostDefinition()
            ->setSetting('fieldsForApplication', [
              'performerFees',
              'otherFees',
              'personnelSideCosts',
              'equipment',
              'premises',
              'marketing',
              'showCosts',
              'travelCosts',
              'transportCosts',
            ])
        );

      $info['muu_huomioitava_panostus'] = DataDefinition::create('string')
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
   * Checks if subvention amount is high enough to include the field to ATV.
   */
  public static function activityConditionCheck(DataDefinitionInterface $definition, array $documentData) {
    $subventions = $documentData['subventions'] ?? [];

    $subventionsTotalAmount = 0;

    foreach ($subventions as $subventionData) {
      if (isset($subventionData['amount'])) {
        $subventionsTotalAmount += GrantsHandler::convertToFloat($subventionData['amount']);
      }
    }

    return $subventionsTotalAmount > 5000;
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

<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition;

/**
 * Define KuvaPerus definitions data.
 */
class KuvaPerusDefinition extends ComplexDataDefinitionBase {

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
        ->setLabel('Ensisijainen taiteenala')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'primaryArt',
        ]);

      $info['hankkeen_tai_toiminnan_lyhyt_esittelyteksti'] = DataDefinition::create('string')
        ->setLabel('Hankkeen tai toiminnan lyhyt esittelyteksti')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'purpose',
        ]);

      // 3. Yhteisön tiedot.
      $info['members_applicant_person_global'] = DataDefinition::create('integer')
        ->setLabel('Henkilöjäsenet')
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
        ->setLabel('Näistä helsinkiläisiä')
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
        ->setLabel('Yhteisöjäsenet')
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
        ->setLabel('Näistä helsinkiläisiä')
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

      $info['taiteellisen_toiminnan_tilaa_omistuksessa_tai_ymparivuotisesti_p'] = DataDefinition::create('boolean')
        ->setLabel('Taiteellisen toiminnan tilaa omistuksessa tai ympärivuotisesti päävuokralaisena.')
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
        ->setLabel('Tilat')
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
          'isOwnedByApplicant',
          'postCode',
          'isOwnedByCity',
        ]);

      // 4. Toiminta.
      $info['varhaisian_opinnot'] = DataDefinition::create('integer')
        ->setLabel('Varhaisiän opinnot, Kaikki')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'pupilsChildhoodAll',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['laaja_oppimaara_perusopinnot'] = DataDefinition::create('integer')
        ->setLabel('Laaja oppimäärä perusopinnot')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'wideBasicStudiesAll',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['laaja_oppimaara_syventavat_opinnot'] = DataDefinition::create('integer')
        ->setLabel('Laaja oppimäärä syventävät opinnot')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'wideAdvancedStudiesAll',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['yleinen_oppimaara'] = DataDefinition::create('integer')
        ->setLabel('Yleinen oppimäärä')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'generalStudiesAll',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['tytot_varhaisian_opinnot'] = DataDefinition::create('integer')
        ->setLabel('Varhaisiän opinnot, Tytöt')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'pupilsChildhoodGirls',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['tytot_laaja_oppimaara_perusopinnot'] = DataDefinition::create('integer')
        ->setLabel('Laaja oppimärä, Tytöt')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'wideBasicStudiesGirls',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['tytot_laaja_oppimaara_syventavat_opinnot'] = DataDefinition::create('integer')
        ->setLabel('Laaja oppimäärä syventävät opinnot, Tytöt')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'wideAdvancedStudiesGirls',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['tytot_yleinen_oppimaara'] = DataDefinition::create('integer')
        ->setLabel('Yleinen oppimäärä, Tytöt')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'generalStudiesGirls',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['pojat_varhaisian_opinnot'] = DataDefinition::create('integer')
        ->setLabel('Varhaisiän opinnot, Pojat')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'pupilsChildhoodBoys',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['pojat_laaja_oppimaara_perusopinnot'] = DataDefinition::create('integer')
        ->setLabel('Laaja oppimärä, Pojat')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'wideBasicStudiesBoys',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['pojat_laaja_oppimaara_syventavat_opinnot'] = DataDefinition::create('integer')
        ->setLabel('Laaja oppimäärä syventävät opinnot, Pojat')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'wideAdvancedStudiesBoys',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['pojat_yleinen_oppimaara'] = DataDefinition::create('integer')
        ->setLabel('Yleinen oppimäärä, Pojat')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'generalStudiesBoys',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['koko_opetushenkiloston_lukumaara_20_9'] = DataDefinition::create('integer')
        ->setLabel('Koko opetushenkilöstön lukumäärä 20.9')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'teachingPersonnel',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['kuvaile_oppilaaksi_ottamisen_tapaa'] = DataDefinition::create('string')
        ->setLabel('Kuvaile oppilaaksi ottamisen tapaa')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'wayOfSelection',
        ]);

      $info['tehdaanko_oppilaitoksessanne_tarvittaessa_oppimaaran_tai_opetuks'] = DataDefinition::create('string')
        ->setLabel('Tehdäänkö oppilaitoksessanne tarvittaessa oppimäärän tai opetuksen yksilöllistämistä?')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'personalTeaching',
        ]);

      $info['onko_vapaa_oppilaspaikkoja_montako_'] = DataDefinition::create('string')
        ->setLabel('Onko vapaaoppilaspaikkoja, montako?')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'freeStudents',
        ]);

      $info['opetustunnit_varhaisian_opinnot'] = DataDefinition::create('integer')
        ->setLabel('Varhaisiän opinnot')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'lessonsChildhood',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['opetustunnit_laaja_oppimaara_perusopinnot'] = DataDefinition::create('integer')
        ->setLabel('Varhaisiän opinnot')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'lessonsWideBasicStudies',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['opetustunnit_laaja_oppimaara_syventavat_opinnot'] = DataDefinition::create('integer')
        ->setLabel('Varhaisiän opinnot')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'lessonsWideAdvancedStudies',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['opetustunnit_yleinen_oppimaara'] = DataDefinition::create('integer')
        ->setLabel('Varhaisiän opinnot')
        ->setSetting('jsonPath', [
          'compensation',
          'activityInfo',
          'realizedActivityInfoArray',
          'lessonsGeneralStudies',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['helsingissa_jarjestettava_tila'] = ListDataDefinition::create('grants_premises')
        ->setLabel('Tilat')
        ->setSetting('jsonPath', [
          'compensation',
          'communityInfo',
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
          'premiseType',
          'isOthersUse',
          'isOwnedByApplicant',
          'postCode',
          'isOwnedByCity',
          'citySection',
          'premiseSuitability',
        ]);

      // 5. Toiminnan lähtökohdat.
      $info['toiminta_tasa_arvo'] = DataDefinition::create('string')
        ->setLabel('Miten monimuotoisuus ja tasa-arvo toteutuu ja näkyy toiminnan järjestäjissä ja organisaatioissa sekä toiminnan sisällöissä? Minkälaisia toimenpiteitä, resursseja ja osaamista on asian edistämiseksi?')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_tasa_arvo',
        ]);

      $info['toiminta_saavutettavuus'] = DataDefinition::create('string')
        ->setLabel('Miten toiminta tehdään kaupunkilaiselle sosiaalisesti, kulttuurisesti, kielellisesti, taloudellisesti, fyysisesti, alueellisesti tai muutoin mahdollisimman saavutettavaksi? Minkälaisia toimenpiteitä, resursseja ja osaamista on asian edistämiseksi?')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_saavutettavuus',
        ]);

      $info['toiminta_ekologisuus'] = DataDefinition::create('string')
        ->setLabel('Miten ekologisuus huomioidaan toiminnan järjestämisessä? Minkälaisia toimenpiteitä, resursseja ja osaamista on asian edistämiseksi?')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_ekologisuus',
        ]);

      $info['toiminta_tavoitteet'] = DataDefinition::create('string')
        ->setLabel('Mitkä olivat keskeisimmät edelliselle kaudelle asetetut tavoitteet ja saavutettiinko ne?')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_tavoitteet',
        ]);

      $info['toiminta_kaytetyt_keinot'] = DataDefinition::create('string')
        ->setLabel('Millaisia keinoja käytetään itsearviointiin ja toiminnan kehittämiseen?')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_kaytetyt_keinot',
        ]);

      $info['toiminta_tulevat_muutokset'] = DataDefinition::create('string')
        ->setLabel('	Mitkä ovat tulevalle vuodelle suunnitellut keskeisimmät muutokset toiminnassa ja sen järjestämisessä suhteessa aikaisempaan?')
        ->setSetting('jsonPath', [
          'compensation',
          'activityBasisInfo',
          'activityBasisArray',
          'toiminta_tulevat_muutokset',
        ]);

      // 6. Talous.
      $info['organisaatio_kuuluu_valtionosuusjarjestelmaan_vos_'] = DataDefinition::create('boolean')
        ->setLabel('Organisaatio kuuluu valtionosuusjärjestelmään (VOS).')
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
        ->setLabel('Organisaatio kuului valtionosuusjärjestelmään (VOS).')
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

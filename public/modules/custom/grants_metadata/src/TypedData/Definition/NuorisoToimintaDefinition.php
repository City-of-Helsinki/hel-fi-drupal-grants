<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Define Yleisavustushakemus data.
 */
class NuorisoToimintaDefinition extends ComplexDataDefinitionBase {

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

      $info['haen_vuokra_avustusta'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'rentalApplied',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['jasenet_0_6_vuotiaat'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'membersAge0to6YearsGlobal',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['0_6_joista_helsinkilaisia'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'membersAge0to6YearsLocal',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['jasenet_7_28_vuotiaat'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'membersAge7to28YearsGlobal',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['7_28_joista_helsinkilaisia'] = DataDefinition::create('integer')
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

      $info['muut_jasenet_tai_aktiiviset_osallistujat'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'membersOthersGlobal',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['muut_joista_helsinkilaisia'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'membersOthersLocal',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['alle_29_vuotiaiden_kaikki_osallistumiskerrat_edellisena_kalenter'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'lastYearYoungPeopleParticipation',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['joista_alle_29_vuotiaiden_digitaalisia_osallistumiskertoja_oli'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'lastYearYoungPeopleParticipationDigital',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['miten_nuoret_osallistuvat_yhdistyksen_toiminnan_suunnitteluun_ja'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'youngPeopleParticipation',
        ]);

      $info['jarjestimme_toimintaa_vain_digitaalisessa_ymparistossa'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premiseSummaryArray',
          'digitalPremises',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['jarjestimme_toimintaa_nuorille_seuraavissa_paikoissa'] = ListDataDefinition::create('grants_premises')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
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
          'location',
          'streetAddress',
          'postCode',
        ]);

      $info['kuinka_monta_paatoimista_palkattua_tyontekijaa_yhdistyksessa_tyo'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'compensation',
          'hiredOfficialsInfo',
          'amountOfHiredOfficials',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['palkkauskulut'] = DataDefinition::create('float')
        ->setSetting('jsonPath', [
          'compensation',
          'budgetInfo',
          'budgetInfoArray',
          'employeeSalaries',
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

      $info['lakisaateiset_ja_vapaaehtoiset_henkilosivukulut'] = DataDefinition::create('float')
        ->setSetting('jsonPath', [
          'compensation',
          'budgetInfo',
          'budgetInfoArray',
          'mandatoryAndOptionalPersonelCosts',
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

      $info['matka_ja_koulutuskulut'] = DataDefinition::create('float')
        ->setSetting('jsonPath', [
          'compensation',
          'budgetInfo',
          'budgetInfoArray',
          'travelAndTrainingCosts',
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

      $info['jasenyydet_jarjestoissa_ja_muissa_yhteisoissa'] = ListDataDefinition::create('grants_members')
        ->setSetting('jsonPath', [
          'compensation',
          'membershipsInfo',
          'membershipsArray',
        ]);

      $info['vuokratun_tilan_tiedot'] = ListDataDefinition::create('grants_rented_premise')
        ->setSetting('jsonPath', [
          'compensation',
          'rentsInfo',
          'rentedPremisesArray',
        ]);

      $info['lisatiedot'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'rentsInfo',
          'rentsSummaryArray',
          'rentsInformation',
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

<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition;

/**
 * Define Yleisavustushakemus data.
 */
class LiikuntaTapahtumaDefinition extends ComplexDataDefinitionBase {

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
        ]);

      // Section 2. Participants.
      $info['20_men'] = DataDefinition::create('integer')
        ->setLabel('Miehiä')
        ->setSetting('jsonPath', [
          'compensation',
          'participantsArray',
          'adultsMale',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['20_women'] = DataDefinition::create('integer')
        ->setLabel('Naisia')
        ->setSetting('jsonPath', [
          'compensation',
          'participantsArray',
          'adultsFemale',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['20_other'] = DataDefinition::create('integer')
        ->setLabel('Muu')
        ->setSetting('jsonPath', [
          'compensation',
          'participantsArray',
          'adultsOther',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['under_20_men'] = DataDefinition::create('integer')
        ->setLabel('Poikia')
        ->setSetting('jsonPath', [
          'compensation',
          'participantsArray',
          'juniorsMale',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['under_20_women'] = DataDefinition::create('integer')
        ->setLabel('Tyttöjä')
        ->setSetting('jsonPath', [
          'compensation',
          'participantsArray',
          'juniorsFemale',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['under_20_other'] = DataDefinition::create('integer')
        ->setLabel('Muu')
        ->setSetting('jsonPath', [
          'compensation',
          'participantsArray',
          'juniorsOther',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      // Section 2. Event info.
      $info['event_for_applied_grant'] = DataDefinition::create('string')
        ->setLabel('Tapahtuma, johon avustusta haetaan')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventName',
        ]);

      $info['event_target_group'] = DataDefinition::create('string')
        ->setLabel('Tapahtuman kohderyhmä')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventTargetGroup',
        ]);

      $info['event_location'] = DataDefinition::create('string')
        ->setLabel('Tapahtumapaikka')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventPlace',
        ]);

      $info['event_details'] = DataDefinition::create('string')
        ->setLabel('Tapahtuman sisältö')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventContent',
        ]);

      $info['alkaa'] = DataDefinition::create('string')
        ->setLabel('Alkamisaika')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventBegin',
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

      $info['paattyy'] = DataDefinition::create('string')
        ->setLabel('Tapahtuman nimi')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventEnd',
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

      $info['equality_radios'] = DataDefinition::create('string')
        ->setLabel('Tapahtuma edistää yhdenvertaisuutta ja tasa-arvoa?')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'isEventEquality',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['equality_how'] = DataDefinition::create('string')
        ->setLabel('Miten')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventEqualityText',
        ]);

      $info['inclusion_radios'] = DataDefinition::create('string')
        ->setLabel('Tapahtuma edistää osallisuutta ja yhteisöllisyyttä?')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'isEventCommunal',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['inclusion_how'] = DataDefinition::create('string')
        ->setLabel('Miten')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventCommunalText',
        ]);

      $info['environment_radios'] = DataDefinition::create('string')
        ->setLabel('Tapahtumassa on huomioitu ympäristöasiat?')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'isEventEnvironment',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['environment_how'] = DataDefinition::create('string')
        ->setLabel('Miten')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventEnvironmentText',
        ]);

      $info['exercise_radios'] = DataDefinition::create('string')
        ->setLabel('Tapahtuma innostaa uusia harrastajia omatoimisen tai ohjatun liikunnan pariin?')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'isEventNewPeopleActivating',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['exercise_how'] = DataDefinition::create('string')
        ->setLabel('Miten')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventNewPeopleActivatingText',
        ]);

      $info['activity_radios'] = DataDefinition::create('string')
        ->setLabel('Tapahtuma innostaa ihmisiä arkiaktiivisuuteen?')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'isEventWorkdayActivating',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['activity_how'] = DataDefinition::create('string')
        ->setLabel('Miten')
        ->setSetting('jsonPath', [
          'compensation',
          'eventInfoArray',
          '0',
          'eventWorkdayActivatingText',
        ]);

      // Section 3. Budget info.
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

<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Define Asukasosallisuus, yleis- ja toiminta-avustushakemus data.
 */
class AsukasYleisToimDefinition extends ComplexDataDefinitionBase {

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
    }

    $info['members_applicant_person_local'] = DataDefinition::create('integer')
      ->setSetting('defaultValue', "")
      ->setSetting('valueCallback', [
        '\Drupal\grants_metadata\ConvertHelper',
        'convertToInt',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'integer',
        'jsonType' => 'int',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'membersApplicantPersonLocal',
      ]);

    $info['members_applicant_person_global'] = DataDefinition::create('integer')
      ->setSetting('defaultValue', "")
      ->setSetting('valueCallback', [
        '\Drupal\grants_metadata\ConvertHelper',
        'convertToInt',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'membersApplicantPersonGlobal',
      ]);

    $info['members_applicant_community_local'] = DataDefinition::create('integer')
      ->setSetting('defaultValue', "")
      ->setSetting('valueCallback', [
        '\Drupal\grants_metadata\ConvertHelper',
        'convertToInt',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'membersApplicantCommunityLocal',
      ]);

    $info['members_applicant_community_global'] = DataDefinition::create('integer')
      ->setSetting('valueCallback', [
        '\Drupal\grants_metadata\ConvertHelper',
        'convertToInt',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'membersApplicantCommunityGlobal',
      ]);

    $info['subventions'] = ListDataDefinition::create('grants_metadata_compensation_type')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'compensationArray',
      ]);

    $info['purpose'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'purpose',
      ]);

    $info['compensation_boolean'] = DataDefinition::create('boolean')
      ->setRequired(TRUE)
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'bool',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'compensationPreviousYear',
      ]);

    $info['compensation_explanation'] = DataDefinition::create('string')
      ->setSetting('defaultValue', "")
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'explanation',
      ]);

    $info['who_benefits'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'whoBenefits',
      ]);

    $info['changes_on_success'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'changesOnSuccess',
      ]);

    $info['results_of_activities'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'resultsOfActivities',
      ]);

    $info['fee_person'] = DataDefinition::create('float')
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'feePerson',
      ])
      ->setSetting('valueCallback', [
        '\Drupal\grants_metadata\ConvertHelper',
        'convertToFloat',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'float',
      ]);

    $info['fee_community'] = DataDefinition::create('float')
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'feeCommunity',
      ])
      ->setSetting('valueCallback', [
        '\Drupal\grants_metadata\ConvertHelper',
        'convertToFloat',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'float',
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

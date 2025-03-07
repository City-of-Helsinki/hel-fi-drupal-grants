<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Yleisavustushakemus data.
 */
class KaskoYleisavustusDefinition extends ComplexDataDefinitionBase {

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

      $info['members_applicant_person_local'] = DataDefinition::create('string')
        ->setSetting('defaultValue', "")
        ->setSetting('valueCallback', [
          '\Drupal\grants_metadata\ConvertHelper',
          'convertToInt',
        ])
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'membersApplicantPersonLocal',
        ]);

      $info['members_applicant_person_global'] = DataDefinition::create('string')
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

      $info['members_applicant_community_local'] = DataDefinition::create('string')
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

      $info['members_applicant_community_global'] = DataDefinition::create('string')
        ->setSetting('valueCallback', [
          '\Drupal\grants_metadata\ConvertHelper',
          'convertToInt',
        ])
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'membersApplicantCommunityGlobal',
        ]);

      $info['compensation_purpose'] = DataDefinition::create('string')
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

      $info['compensation_total_amount'] = DataDefinition::create('float')
        ->setSetting('defaultValue', 0)
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'float',
        ])
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'totalAmount',
        ])
        ->addConstraint('NotBlank');

      $info['compensation_explanation'] = DataDefinition::create('string')
        ->setSetting('defaultValue', "")
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'generalInfoArray',
          'explanation',
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

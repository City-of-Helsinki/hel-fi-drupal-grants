<?php

namespace Drupal\grants_metadata_test_webforms\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\grants_metadata\TypedData\Definition\ApplicationDefinitionTrait;

/**
 * Define FailedDataDefinition data.
 */
class FailedDataDefinition extends ComplexDataDefinitionBase {

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
        ->setLabel('activitiesInfoArray=>membersApplicantPersonLocal')
        ->setSetting('defaultValue', "")
        ->setSetting('jsonPath', [
          'compensation',
          'activitiesInfoArray',
          'level3',
          'level4',
          'level5',
          'level6',
        ]);

      /*
      $info['myonnetty_avustus_total'] = DataDefinition::create('float')
      ->setLabel('Myönnetty avustus total')
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'double',
      ])
      ->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToFloat',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'otherCompensationsInfo',
        'otherCompensationsInfoArray',
        'otherCompensationsTotal',
        'otherCompensationsTotalLevel5',
        'otherCompensationsTotal6',
      ]);*/
    }
    return $this->propertyDefinitions;
  }
}

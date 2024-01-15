<?php

namespace Drupal\grants_premises\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Application official data.
 */
class GrantsPremisesDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['premiseName'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'premiseName',
        ]);

      $info['premiseAddress'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'premiseAddress',
        ]);

      $info['premiseType'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'premiseType',
        ]);

      $info['location'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'location',
        ]);
      $info['streetAddress'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'streetAddress',
        ]);
      $info['address'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'address',
        ]);
      $info['postCode'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'postCode',
        ]);
      $info['studentCount'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'studentCount',
        ]);
      $info['specialStudents'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'specialStudents',
        ]);
      $info['groupCount'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'groupCount',
        ]);
      $info['specialGroups'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'specialGroups',
        ]);
      $info['personnelCount'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'personnelCount',
        ]);
      $info['totalRent'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'totalRent',
        ]);
      $info['rentTimeBegin'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'rentTimeBegin',
        ]);
      $info['rentTimeEnd'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'rentTimeEnd',
        ]);

      $info['premiseOwnerShip'] = DataDefinition::create('string');

      $info['free'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'free',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['isOthersUse'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'isOthersUse',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['isOwnedByApplicant'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'isOwnedByApplicant',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);
      $info['isOwnedByCity'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'isOwnedByCity',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);
      $info['citySection'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'citySection',
        ]);
      $info['premiseSuitability'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'premiseSuitability',
        ]);
    }
    return $this->propertyDefinitions;
  }

}

<?php

namespace Drupal\grants_place_of_operation\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Place of Operation data.
 */
class PlaceOfOperationDefinition extends ComplexDataDefinitionBase {

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
        ->addConstraint('ValidPostalCode')
        ->setSetting('jsonPath', [
          'postCode',
        ]);

      $info['studentCount'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'studentCount',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['specialStudents'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'specialStudents',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['groupCount'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'groupCount',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['specialGroups'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'specialGroups',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['personnelCount'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'personnelCount',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['free'] = DataDefinition::create('boolean')
        ->setSetting('jsonPath', [
          'free',
        ])
        ->setSetting('webformValueExtracter', [
          'service' => 'grants_place_of_operation.service',
          'method' => 'convertBooleanToString',
        ]);

      $info['totalRent'] = DataDefinition::create('float')
        ->setSetting('jsonPath', [
          'totalRent',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ])
        ->setSetting('webformValueExtracter', [
          'service' => 'grants_metadata.converter',
          'method' => 'extractFloatValue',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ]);

      $info['rentTimeBegin'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'rentTimeBegin',
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

      $info['rentTimeEnd'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'rentTimeEnd',
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

    }
    return $this->propertyDefinitions;
  }

}

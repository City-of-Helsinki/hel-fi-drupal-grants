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
        ->setLabel('Sijainnin nimi')
        ->setSetting('jsonPath', [
          'premiseName',
        ]);

      $info['premiseAddress'] = DataDefinition::create('string')
        ->setLabel('Sijainnin osoite')
        ->setSetting('jsonPath', [
          'premiseAddress',
        ]);

      $info['location'] = DataDefinition::create('string')
        ->setLabel('Sijainti')
        ->setSetting('jsonPath', [
          'location',
        ]);

      $info['streetAddress'] = DataDefinition::create('string')
        ->setLabel('Katuosoite')
        ->setSetting('jsonPath', [
          'streetAddress',
        ]);

      $info['address'] = DataDefinition::create('string')
        ->setLabel('Osoite')
        ->setSetting('jsonPath', [
          'address',
        ]);

      $info['postCode'] = DataDefinition::create('string')
        ->setLabel('Postinumero')
        ->addConstraint('NotBlank')
        ->addConstraint('ValidPostalCode')
        ->setSetting('jsonPath', [
          'postCode',
        ]);

      $info['studentCount'] = DataDefinition::create('string')
        ->setLabel('Oppilaiden lukumäärä')
        ->setSetting('jsonPath', [
          'studentCount',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['specialStudents'] = DataDefinition::create('string')
        ->setLabel('Joista erityisoppilaita')
        ->setSetting('jsonPath', [
          'specialStudents',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['groupCount'] = DataDefinition::create('string')
        ->setLabel('Ryhmien lukumäärä')
        ->setSetting('jsonPath', [
          'groupCount',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['specialGroups'] = DataDefinition::create('string')
        ->setLabel('Joista erityisoppilaiden pienryhmiä')
        ->setSetting('jsonPath', [
          'specialGroups',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['personnelCount'] = DataDefinition::create('string')
        ->setLabel('Henkilöstön lukumäärä')
        ->setSetting('jsonPath', [
          'personnelCount',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['free'] = DataDefinition::create('boolean')
        ->setLabel('Maksuton')
        ->setSetting('jsonPath', [
          'free',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['totalRent'] = DataDefinition::create('string')
        ->setLabel('Euroa yhteensä lukuvuoden aikana')
        ->setSetting('jsonPath', [
          'totalRent',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ]);

      $info['rentTimeBegin'] = DataDefinition::create('string')
        ->setLabel('Vuokra-aika lukuvuoden aikana, alkaen')
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
        ->setLabel('Vuokra-aika lukuvuoden aikana, päättyen')
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

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
          'compensation',
          'premisesInfo',
          'premisesArray',
          'premiseName',
        ]);

      $info['premiseAddress'] = DataDefinition::create('string')
        ->setLabel('Sijainnin osoite')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'premiseAddress',
        ]);

      $info['location'] = DataDefinition::create('string')
        ->setLabel('Sijainti')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'location',
        ]);

      $info['streetAddress'] = DataDefinition::create('string')
        ->setLabel('Katuosoite')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'streetAddress',
        ]);

      $info['address'] = DataDefinition::create('string')
        ->setLabel('Osoite')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'address',
        ]);

      $info['postCode'] = DataDefinition::create('string')
        ->setLabel('Postinumero')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'postCode',
        ]);

      $info['studentCount'] = DataDefinition::create('string')
        ->setLabel('Oppilaiden lukumäärä')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'studentCount',
        ]);

      $info['specialStudents'] = DataDefinition::create('string')
        ->setLabel('Joista erityisoppilaita')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'specialStudents',
        ]);

      $info['groupCount'] = DataDefinition::create('string')
        ->setLabel('Ryhmien lukumäärä')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'groupCount',
        ]);

      $info['specialGroups'] = DataDefinition::create('string')
        ->setLabel('Joista erityisoppilaiden pienryhmiä')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'specialGroups',
        ]);

      $info['personnelCount'] = DataDefinition::create('string')
        ->setLabel('Henkilöstön lukumäärä')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'personnelCount',
        ]);

      $info['free'] = DataDefinition::create('boolean')
        ->setLabel('Maksuton')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'free',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['totalRent'] = DataDefinition::create('string')
        ->setLabel('Euroa yhteensä lukuvuoden aikana')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'totalRent',
        ]);

      $info['rentTimeBegin'] = DataDefinition::create('string')
        ->setLabel('Vuokra-aika lukuvuoden aikana, alkaen')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'rentTimeBegin',
        ]);

      $info['rentTimeEnd'] = DataDefinition::create('string')
        ->setLabel('Vuokra-aika lukuvuoden aikana, päättyen')
        ->setSetting('jsonPath', [
          'compensation',
          'premisesInfo',
          'premisesArray',
          'rentTimeEnd',
        ]);

    }
    return $this->propertyDefinitions;
  }

}

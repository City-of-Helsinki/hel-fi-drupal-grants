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

      $info['postCode'] = DataDefinition::create('string')
        ->setLabel('Postinumero')
        ->setSetting('jsonPath', [
          'postCode',
        ]);

      $info['studentCount'] = DataDefinition::create('string')
        ->setLabel('description')
        ->setSetting('jsonPath', [
          'studentCount',
        ]);

      $info['specialStudents'] = DataDefinition::create('string')
        ->setLabel('description')
        ->setSetting('jsonPath', [
          'specialStudents',
        ]);

      $info['groupCount'] = DataDefinition::create('string')
        ->setLabel('description')
        ->setSetting('jsonPath', [
          'groupCount',
        ]);

      $info['specialGroups'] = DataDefinition::create('string')
        ->setLabel('description')
        ->setSetting('jsonPath', [
          'specialGroups',
        ]);

      $info['personnelCount'] = DataDefinition::create('string')
        ->setLabel('description')
        ->setSetting('jsonPath', [
          'personnelCount',
        ]);

      $info['free'] = DataDefinition::create('boolean')
        ->setLabel('description')
        ->setSetting('jsonPath', [
          'free',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ]);

      $info['totalRent'] = DataDefinition::create('string')
        ->setLabel('description')
        ->setSetting('jsonPath', [
          'totalRent',
        ]);

      $info['rentTimeBegin'] = DataDefinition::create('string')
        ->setLabel('description')
        ->setSetting('jsonPath', [
          'rentTimeBegin',
        ]);

      $info['rentTimeEnd'] = DataDefinition::create('string')
        ->setLabel('description')
        ->setSetting('jsonPath', [
          'rentTimeEnd',
        ]);

    }
    return $this->propertyDefinitions;
  }

}

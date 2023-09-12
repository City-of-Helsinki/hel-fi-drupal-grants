<?php

namespace Drupal\grants_members\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Members data.
 */
class GrantsMembersDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['organizationName'] = DataDefinition::create('string')
        ->setLabel('Järjestön tai yhteisön nimi')
        ->setSetting('jsonPath', [
          'organizationName',
        ]);

      $info['fee'] = DataDefinition::create('float')
        ->setLabel('Jäsenmaksu, euroa')
        ->setSetting('jsonPath', [
          'fee',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToFloat',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'double',
        ]);

    }
    return $this->propertyDefinitions;
  }

}

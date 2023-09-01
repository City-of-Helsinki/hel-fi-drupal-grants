<?php

namespace Drupal\grants_club_section\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define club section data.
 */
class GrantsClubSectionDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['sectionName'] = DataDefinition::create('string')
        ->setLabel('Laji')
        ->setSetting('jsonPath', [
          'sectionName',
        ]);
      $info['women'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'woman',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['men'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'men',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['adultOthers'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'adultOthers',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['adultHours'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'adultHours',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['boys'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'boys',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['girls'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'girls',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);

      $info['juniorOthers'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'juniorOthers',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
      $info['juniorHours'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', [
          'juniorHours',
        ])->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ]);
    }
    return $this->propertyDefinitions;
  }

}

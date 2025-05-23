<?php

declare(strict_types=1);

namespace Drupal\grants_profile\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Application official data.
 */
class ApplicationOfficialDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['name'] = DataDefinition::create('string')
        ->setSetting('jsonPath', ['grantsProfile', 'officialsArray', 'name'])
        ->setRequired(TRUE)
        ->addConstraint('NotBlank');

      $info['role'] = DataDefinition::create('integer')
        ->setSetting('jsonPath', ['grantsProfile', 'officialsArray', 'role'])
        ->setSetting('valueCallback', [
          '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
          'convertToInt',
        ])
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ])
        ->setSetting('defaultValue', 0)
        ->addConstraint('RequiredIfRegistered');

      $info['email'] = DataDefinition::create('string')
        ->setSetting('jsonPath', ['grantsProfile', 'officialsArray', 'email'])
        ->addConstraint('Email')
        ->addConstraint('NotBlank')
        ->setRequired(TRUE);

      $info['phone'] = DataDefinition::create('string')
        ->setSetting('jsonPath', ['grantsProfile', 'officialsArray', 'phone'])
        ->setRequired(TRUE)
        ->addConstraint('NotBlank');

    }
    return $this->propertyDefinitions;
  }

}

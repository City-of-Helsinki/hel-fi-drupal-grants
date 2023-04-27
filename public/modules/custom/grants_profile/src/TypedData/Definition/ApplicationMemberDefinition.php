<?php

namespace Drupal\grants_profile\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Application member data.
 */
class ApplicationMemberDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['name'] = DataDefinition::create('string')
        ->setLabel('Name')
        ->setSetting('jsonPath', ['grantsProfile', 'membersArray', 'name'])
        ->setRequired(TRUE)
        ->addConstraint('NotBlank');

      $info['email'] = DataDefinition::create('string')
        ->setLabel('Email address')
        ->setSetting('jsonPath', ['grantsProfile', 'membersArray', 'email'])
        ->addConstraint('Email')
        ->addConstraint('NotBlank')
        ->setRequired(TRUE);

      $info['phone'] = DataDefinition::create('string')
        ->setLabel('Phone number')
        ->setSetting('jsonPath', ['grantsProfile', 'membersArray', 'phone'])
        ->addConstraint('NotBlank')
        ->setRequired(TRUE);

      $info['additional'] = DataDefinition::create('string')
        ->setLabel('Additional information')
        ->setSetting('jsonPath', ['grantsProfile', 'membersArray', 'additional']);

    }
    return $this->propertyDefinitions;
  }

}

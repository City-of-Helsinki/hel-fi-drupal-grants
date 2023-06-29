<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Define Yleisavustushakemus data.
 */
class NuorisoToimintaEnnakkoDefinition extends ComplexDataDefinitionBase {

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

      $info['subventions'] = ListDataDefinition::create('grants_metadata_compensation_type')
        ->setLabel('compensationArray')
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'compensationArray',
        ]);

      $info['subventionsPreviousYear'] = ListDataDefinition::create('grants_metadata_compensation_previous_year')
        ->setLabel('compensationArray')
        ->setSetting('fullItemValueCallback', [
          'service' => 'grants_metadata.compensation_service',
          'method' => 'processPreviousYearCompensations',
          'webform' => TRUE,
          'submittedData' => TRUE,
        ])
        ->setSetting('webformDataExtracter', [
          'service' => 'grants_metadata.compensation_service',
          'method' => 'extractDataForWebformPreviousYear',
          'mergeResults' => TRUE,
        ])
        ->setSetting('jsonPath', [
          'compensation',
          'compensationInfo',
          'previousYearArray',
        ]);

      $info['sanallinen_selvitys_avustuksen_kaytosta'] = DataDefinition::create('string')
        ->setLabel('Selvityksen kommentti')
        ->setSetting('jsonPath', [
          'compensation',
          'generalInfoArray',
          'explanation',
        ]);

    }

    return $this->propertyDefinitions;
  }

  /**
   * Override property definition.
   *
   * @param string $name
   *   Property name.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface|void|null
   *   Property definition.
   */
  public function getPropertyDefinition($name) {
    $retval = parent::getPropertyDefinition($name);
    return $retval;
  }

}

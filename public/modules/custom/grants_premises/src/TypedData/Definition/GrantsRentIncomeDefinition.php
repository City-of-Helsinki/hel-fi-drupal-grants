  <?php

  namespace Drupal\grants_premises\TypedData\Definition;

  use Drupal\Core\TypedData\ComplexDataDefinitionBase;
  use Drupal\Core\TypedData\DataDefinition;

  /**
   * Define Rent income data.
   */
  class GrantsRentIncomeDefinition extends ComplexDataDefinitionBase {

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

        $info['dateBegin'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'dateBegin',
          ])
          ->setSetting('typeOverride', [
            'dataType' => 'string',
            'jsonType' => 'datetime',
          ]);

        $info['dateEnd'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'dateEnd',
          ])
          ->setSetting('typeOverride', [
            'dataType' => 'string',
            'jsonType' => 'datetime',
          ]);

        $info['tenantName'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'tenantName',
          ]);

        $info['hours'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'hours',
          ])
          ->setSetting('valueCallback', [
            '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
            'convertToInt',
          ])
          ->setSetting('typeOverride', [
            'dataType' => 'string',
            'jsonType' => 'int',
          ]);

        $info['sum'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'sum',
          ])
          ->setSetting('valueCallback', [
            '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
            'convertToFloat',
          ])
          ->setSetting('webformValueExtracter', [
            'service' => 'grants_metadata.converter',
            'method' => 'convertToCommaFloat',
          ])
          ->setSetting('typeOverride', [
            'dataType' => 'string',
            'jsonType' => 'double',
          ]);

      }
      return $this->propertyDefinitions;
    }

  }

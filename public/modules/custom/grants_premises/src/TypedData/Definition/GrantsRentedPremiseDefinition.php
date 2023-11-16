  <?php

  namespace Drupal\grants_premises\TypedData\Definition;

  use Drupal\Core\TypedData\ComplexDataDefinitionBase;
  use Drupal\Core\TypedData\DataDefinition;

  /**
   * Define Rented premise data.
   */
  class GrantsRentedPremiseDefinition extends ComplexDataDefinitionBase {

    /**
     * {@inheritdoc}
     */
    public function getPropertyDefinitions(): array {
      if (!isset($this->propertyDefinitions)) {
        $info = &$this->propertyDefinitions;

        $info['premiseAddress'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'premiseAddress',
          ]);

        $info['premisePostalCode'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'premisePostalCode',
          ]);

        $info['premisePostOffice'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'premisePostOffice',
          ]);

        $info['rentSum'] = DataDefinition::create('float')
          ->setLabel('Vuokra')
          ->setSetting('jsonPath', [
            'rentSum',
          ])->setSetting('valueCallback', [
            '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
            'convertToFloat',
          ])->setSetting('webformValueExtracter', [
            'service' => 'grants_metadata.converter',
            'method' => 'extractFloatValue',
          ])
          ->setSetting('typeOverride', [
            'dataType' => 'string',
            'jsonType' => 'double',
          ]);

        $info['usage'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'usage',
          ]);

        $info['daysPerWeek'] = DataDefinition::create('integer')
          ->setLabel('Päiviä viikossa')
          ->setSetting('jsonPath', [
            'hoursPerDay',
          ])->setSetting('valueCallback', [
            '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
            'convertToInt',
          ])
          ->setSetting('typeOverride', [
            'dataType' => 'string',
            'jsonType' => 'int',
          ]);

        $info['hoursPerDay'] = DataDefinition::create('integer')
          ->setLabel('Tunteja päivässä')
          ->setSetting('jsonPath', [
            'hoursPerDay',
          ])->setSetting('valueCallback', [
            '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
            'convertToInt',
          ])
          ->setSetting('typeOverride', [
            'dataType' => 'string',
            'jsonType' => 'int',
          ]);
        $info['lessorName'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'lessorName',
          ]);

        $info['lessorPhoneOrEmail'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'lessorPhoneOrEmail',
          ]);

        $info['lessorAddress'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'lessorAddress',
          ]);

        $info['lessorPostalCode'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'lessorPostalCode',
          ]);

        $info['lessorPostOffice'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'lessorPostOffice',
          ]);

      }
      return $this->propertyDefinitions;
    }

  }

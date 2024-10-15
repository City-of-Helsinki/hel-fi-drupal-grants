<?php

namespace Drupal\grants_metadata\Plugin\DataType;

use Drupal\grants_metadata\AtvSchema;

/**
 * Trait to handle generic value settings for implementing data.
 */
trait DataFormatTrait {

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {

    $formattedData = [];
    foreach ($this->getProperties() as $name => $property) {
      $definition = $property->getDataDefinition();

      $defaultValue = $definition->getSetting('defaultValue');
      $valueCallback = $definition->getSetting('valueCallback');
      $itemTypes = AtvSchema::getJsonTypeForDataType($definition);

      $type = $definition->getDataType();

      if (!is_array($values)) {
        $formattedData[$name] = NULL;
      }
      else {
        if (array_key_exists($name, $values)) {
          $value = $values[$name];

          if ($value === NULL) {
            $value = $defaultValue;
          }

          // Force empty strings to null value, so data type checks won't fail.
          if (in_array($type, ['integer', 'float', 'double']) && $value === '') {
            $formattedData[$name] = NULL;
            continue;
          }

          if ($valueCallback) {
            $value = AtvSchema::getItemValue($itemTypes, $value, $defaultValue, $valueCallback);
          }
          $formattedData[$name] = $value;
        }
      }
    }

    parent::setValue($formattedData, $notify);
  }

  /**
   * Override getValue to be able to debug better.
   *
   * @return array
   *   The value.
   */
  public function getValue(): array {
    $retval = parent::getValue();
    return $retval;
  }

}

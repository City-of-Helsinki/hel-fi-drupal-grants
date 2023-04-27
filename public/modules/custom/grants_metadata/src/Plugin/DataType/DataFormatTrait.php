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

      if (!is_array($values)) {
        $formattedData[$name] = NULL;
      }
      else {
        if (array_key_exists($name, $values)) {
          $value = $values[$name];

          if ($value === NULL) {
            $value = $defaultValue;
          }

          if ($valueCallback) {
            $value = AtvSchema::getItemValue($itemTypes, $value, $defaultValue, $valueCallback);
          }
          $formattedData[$name] = $value;
        }
      }
    }

    // @todo Change the autogenerated stub
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

<?php

namespace Drupal\grants_premises;

use Drupal\Core\TypedData\ListInterface;
use Drupal\grants_metadata\AtvSchema;

/**
 * Useful tools for premises fields.
 */
class GrantsPremisesService {

  /**
   * Parse premises.
   *
   * @param \Drupal\Core\TypedData\ListInterface $property
   *   Property that is handled.
   * @param array $arguments
   *   Any extra arguments, eg used webform for meta fields.
   *
   * @return array
   *   Processed items.
   */
  public function processPremises(ListInterface $property, array $arguments): array {

    $items = [];

    $dataDefinition = $property->getDataDefinition();
    $usedFields = $dataDefinition->getSetting('fieldsForApplication');

    foreach ($property as $itemIndex => $p) {
      $itemValues = [];

      foreach ($p as $item) {
        $itemName = $item->getName();
        $itemDef = $item->getDataDefinition();

        // If this item is not selected for jsonData.
        if (!in_array($itemName, $usedFields)) {
          // Just continue...
          continue;
        }

        // Get item value types from item definition.
        $itemDefinition = $item->getDataDefinition();
        $valueTypes = AtvSchema::getJsonTypeForDataType($itemDefinition);
        $defaultValue = $itemDef->getSetting('defaultValue');
        $valueCallback = $itemDef->getSetting('valueCallback');

        $itemValue = AtvSchema::getItemValue($valueTypes, $item->getValue(), $defaultValue, $valueCallback);

        if (!$itemValue) {
          continue;
        }

        // Process boolean values separately.
        if (
          $itemName == 'isOwnedByCity' ||
          $itemName == 'isOthersUse' ||
          $itemName == 'isOwnedByApplicant'
        ) {
          $itemValues[] = [
            'ID' => $itemName,
            'label' => $itemDefinition->getLabel(),
            'value' => $itemValue,
            'valueType' => $valueTypes['jsonType'],
            'meta' => self::getMeta(),
          ];
          continue;
        }
        // Add items.
        $itemValues[] = [
          'ID' => $itemName,
          'label' => $itemDefinition->getLabel(),
          'value' => $itemValue,
          'valueType' => $valueTypes['jsonType'],
          'meta' => self::getMeta(),
        ];
      }
      $items[$itemIndex] = $itemValues;
    }
    return $items;
  }

  /**
   * Get meta field data from components.
   *
   * So far only to return empty to support structure, will be filled in time.
   *
   * @return string
   *   Metadata.
   */
  private function getMeta(): string {
    return '{}';
  }

}

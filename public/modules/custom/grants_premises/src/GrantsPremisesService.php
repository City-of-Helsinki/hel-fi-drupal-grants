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
   *
   * @return array
   *   Processed items.
   */
  public function processPremises(ListInterface $property): array {

    $items = [];

    $dataDefinition = $property->getDataDefinition();
    $usedFields = $dataDefinition->getSetting('fieldsForApplication');

    foreach ($property as $itemIndex => $p) {
      $itemValues = [];
      foreach ($p as $item) {
        $itemName = $item->getName();

        // If this item is not selected for jsonData.
        if (!in_array($itemName, $usedFields)) {
          // Just continue...
          continue;
        }
        // Get item value types from item definition.
        $itemDefinition = $item->getDataDefinition();
        $valueTypes = AtvSchema::getJsonTypeForDataType($itemDefinition);

        // Process boolean values separately.
        if ($itemName == 'isOwnedByCity' || $itemName == 'isOwnedByApplicant') {
          $itemValues[] = [
            'ID' => $itemName,
            'label' => $itemDefinition->getLabel(),
            'value' => $item->getValue(),
            'valueType' => $valueTypes['jsonType'],
          ];
          continue;
        }
        // Add items.
        $itemValues[] = [
          'ID' => $itemName,
          'label' => $itemDefinition->getLabel(),
          'value' => $item->getValue(),
          'valueType' => $valueTypes['jsonType'],
        ];
      }
      $items[$itemIndex] = $itemValues;
    }
    return $items;
  }

}

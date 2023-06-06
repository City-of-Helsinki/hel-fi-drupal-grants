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

      ['page' => $pageMeta, 'section' => $sectionMeta] = $this->getWebformMeta(
        $arguments['webform'] ?? [],
        $property
      );

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

        $elementMeta = self::getMeta($itemDefinition);
        $completeMeta = json_encode(AtvSchema::getMetaData(
          $pageMeta, $sectionMeta, $elementMeta,
        ));

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
            'meta' => $completeMeta,
          ];
          continue;
        }
        // Add items.
        $itemValues[] = [
          'ID' => $itemName,
          'label' => $itemDefinition->getLabel(),
          'value' => $itemValue,
          'valueType' => $valueTypes['jsonType'],
          'meta' => $completeMeta,
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
   * @return array
   *   Metadata.
   */
  private function getMeta($itemDefinition): array {
    return [
      'label' => $itemDefinition->getLabel(),
    ];
  }

  /**
   * Get meta field data to webform page and section parts.
   */
  private function getWebformMeta($webform, $property): array {

    if (empty($webform)) {
      return [
        'page' => [],
        'section' => [],
      ];
    }

    $webformMainElement = $webform->getElement($property->getName());
    $elements = $webform->getElementsDecodedAndFlattened();
    $elementKeys = array_keys($elements);

    $pages = $webform->getPages('edit');

    $pageId = $webformMainElement['#webform_parents'][0];
    $pageKeys = array_keys($pages);
    $pageLabel = $pages[$pageId]['#title'];
    $pageNumber = array_search($pageId, $pageKeys) + 1;

    $sectionId = $webformMainElement['#webform_parents'][1];
    $sectionLabel = $elements[$sectionId]['#title'];
    $sectionWeight = array_search($sectionId, $elementKeys);

    $page = [
      'id' => $pageId,
      'label' => $pageLabel,
      'number' => $pageNumber,
    ];

    $section = [
      'id' => $sectionId,
      'label' => $sectionLabel,
      'weight' => $sectionWeight,
    ];

    return [
      'page' => $page,
      'section' => $section,
    ];
  }

}

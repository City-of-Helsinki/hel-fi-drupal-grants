<?php

namespace Drupal\grants_premises;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\grants_metadata\AtvSchema;
use Drupal\webform\Entity\Webform;

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
    $propertyName = $property->getName();
    $webformElement = [];
    if (isset($arguments['webform'])) {
      $webformElement = $arguments['webform']->getElement($propertyName);
    }
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
        $label = $itemDefinition->getLabel();
        if (isset($webformElement['#webform_composite_elements'][$itemName]['#title'])) {
          $label = $webformElement['#webform_composite_elements'][$itemName]['#title'];
          if (!is_string($label)) {
            $label = $label->render();
          }
        }
        $elementMeta = self::getMeta($label);
        $metaData = AtvSchema::getMetaData($pageMeta, $sectionMeta, $elementMeta);
        $completeMeta = json_encode($metaData, JSON_UNESCAPED_UNICODE);

        // Add items.
        $itemValues[] = [
          'ID' => $itemName,
          'label' => $label,
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
   * So far only to return label to support structure, will be filled in time.
   *
   * @param string $label
   *   Label of the field.
   *
   * @return array
   *   Metadata.
   */
  private static function getMeta($label): array {
    return [
      'label' => $label,
    ];
  }

  /**
   * Get meta field data to webform page and section parts.
   *
   * @param \Drupal\webform\Entity\Webform|null $webform
   *   Webform.
   * @param \Drupal\Core\TypedData\ListInterface $property
   *   Element property.
   *
   * @return array
   *   Metadata
   */
  private function getWebformMeta(? Webform $webform, ListInterface $property): array {

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

  /**
   * Extract values in correct structure from document data.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   Data definition.
   * @param array $documentData
   *   Full data.
   *
   * @return array
   *   Structured content
   */
  public function extractToWebformData(DataDefinitionInterface $definition, array $documentData): array {

    $settings = $definition->getSettings();
    $data = NestedArray::getValue($documentData, $settings['jsonPath']);

    if (!$data) {
      return [];
    }

    $retval = [];
    foreach ($data as $key => $value) {
      $temp = [];
      foreach ($value as $v2) {
        if ($v2['valueType'] === 'bool') {
          if ($v2['value'] === 'true') {
            $vv = 1;
          }
          if ($v2['value'] === 'false') {
            $vv = 0;
          }
        }
        else {
          $vv = $v2['value'];
        }
        $temp[$v2['ID']] = $vv;
      }
      $retval[$key] = $temp;
    }
    return $retval;
  }

}

<?php

namespace Drupal\grants_budget_components;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\TypedData\ListInterface;
use Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler;
use Drupal\grants_metadata\AtvSchema;

/**
 * Useful tools for budget components.
 */
class GrantsBudgetComponentService {

  const IGNORED_FIELDS = [
    'costGroupName',
    'incomeGroupName',
  ];

  /**
   * Parse budget income fields.
   *
   * @param \Drupal\Core\TypedData\ListInterface $property
   *   Property that is handled.
   *
   * @return array
   *   Processed items.
   */
  public static function processBudgetStaticValues($property): array {
    $items = [];

    foreach ($property as $item) {
      $itemName = $item->getName();

      // Get item value types from item definition.
      $itemDefinition = $item->getDataDefinition();
      $valueTypes = AtvSchema::getJsonTypeForDataType($itemDefinition);

      if (!in_array($itemName, self::IGNORED_FIELDS)) {

        $value = $item->getValue();

        if (is_null($value) || $value === "") {
          continue;
        }

        $items[] = [
          'ID' => $itemName,
          'label' => $itemDefinition->getLabel(),
          'value' => (string) GrantsHandler::convertToFloat($value),
          'valueType' => $valueTypes['jsonType'],
        ];
      }
    }
    return $items;
  }

  /**
   * Format Other Income/Cost values to ATV Schema format.
   *
   * @param \Drupal\Core\TypedData\ListInterface $property
   *   ListInterface property.
   *
   * @return array
   *   Formatted data.
   */
  public static function processBudgetOtherValues(ListInterface $property): array {
    $items = [];
    $index = 0;
    foreach ($property as $itemIndex => $p) {
      $values = $p->getValue();

      if (!isset($values['value'])) {
        continue;
      }

      $value = $values['value'];

      if (is_null($value) || $value === "") {
        continue;
      }

      $itemValues = [
        'ID' => $property->getName() . '_' . $index,
        'label' => $values['label'] ?? NULL,
        'value' => (string) GrantsHandler::convertToFloat($value),
        'valueType' => 'double',
      ];

      $items[$itemIndex] = $itemValues;
      $index++;
    }
    return $items;
  }

  /**
   * Transform ATV Data to Webform.
   *
   * @param array $documentData
   *   Document data from ATV.
   * @param array $jsonPath
   *   Json path as array.
   *
   * @return array
   *   Formatted data.
   */
  public static function getBudgetOtherValues(array $documentData, array $jsonPath): array {

    $retVal = [];

    $pathLast = array_pop($jsonPath);

    $elements = NestedArray::getValue(
      $documentData,
      $jsonPath
    );

    if (!$elements) {
      return $retVal;
    }

    foreach ($elements as $parent) {
      $groupName = $parent['costGroupName'] ?? $parent['incomeGroupName'];
      if (!empty($parent) && isset($parent[$pathLast])) {
        $retVal[$groupName] = array_map(function ($e) {
          return [
            'label' => $e['label'] ?? NULL,
            'value' => str_replace('.', ',', $e['value']) ?? NULL,
          ];
        }, $parent[$pathLast]);
      }
    }

    return $retVal;
  }

  /**
   * Get Budget income static values in webform format.
   *
   * @param array $documentData
   *   ATV document data.
   * @param array $jsonPath
   *   Json path as array.
   *
   * @return array
   *   Formatted Data.
   */
  public static function getBudgetStaticValues(array $documentData, array $jsonPath) {
    $retVal = [];

    $pathLast = array_pop($jsonPath);

    $elements = NestedArray::getValue(
      $documentData,
      $jsonPath
    );

    if (!$elements) {
      return $retVal;
    }

    foreach ($elements as $parent) {

      if (!empty($parent) && isset($parent[$pathLast])) {
        $groupName = $parent['costGroupName'] ?? $parent['incomeGroupName'];
        $values = [];
        foreach ($parent[$pathLast] as $row) {
          $row['value'] = str_replace('.', ',', $row['value']);
          $values[$row['ID']] = $row['value'];
        }
        $retVal[$groupName][] = $values;

      }
    }
    return $retVal;
  }

  /**
   * Extract typed data to webform format based definition.
   *
   * @return array
   *
   *   Formatted data.
   */
  public static function extractToWebformData($definition, array $documentData) {

    $retVal = [];

    $jsonPathMappings = [
      'budget_static_income' => [
        'compensation',
        'budgetInfo',
        'incomeGroupsArrayStatic',
        'incomeRowsArrayStatic',
      ],
      'budget_other_income' => [
        'compensation',
        'budgetInfo',
        'incomeGroupsArrayStatic',
        'otherIncomeRowsArrayStatic',
      ],
      'budget_static_cost' => [
        'compensation',
        'budgetInfo',
        'costGroupsArrayStatic',
        'costRowsArrayStatic',
      ],
      'budget_other_cost' => [
        'compensation',
        'budgetInfo',
        'costGroupsArrayStatic',
        'otherCostRowsArrayStatic',
      ],
    ];

    $dataFromDocument = [];

    foreach ($jsonPathMappings as $fieldKey => $jsonPath) {
      $pathLast = end($jsonPath);
      switch ($pathLast) {
        case 'incomeRowsArrayStatic':
        case 'costRowsArrayStatic':
          $dataFromDocument[$pathLast] = self::getBudgetStaticValues(
            $documentData, $jsonPath
          );
          break;

        case 'otherIncomeRowsArrayStatic':
        case 'otherCostRowsArrayStatic':
          $dataFromDocument[$fieldKey] = self::getBudgetOtherValues(
            $documentData, $jsonPath
          );
          break;
      }
    }

    $properties = $definition->getPropertyDefinitions();

    // If additional budget compnents are defined for the application,
    // Check the definitions and add to the webform data.
    foreach ($properties as $propertyKey => $property) {

      $arrayKeys = array_keys($retVal);
      $propertyType = $property->getDataType();
      // No need to check "default budget components".
      if (
        !in_array(
          $propertyType,
          [
            'list',
            'grants_budget_income_static',
            'grants_budget_income_other',
            'grants_budget_cost_static',
            'grants_budget_cost_other',
          ]) ||
        in_array($propertyKey, $arrayKeys)) {
        continue;
      }

      if ($propertyType === 'list') {
        $propertyDef = $property->getItemDefinition();
        $propertyDataType = $propertyDef->getDataType();
        $fieldsForAppilication = $property->getSetting('fieldsForApplication') ?? [];
        $keysToExtract = array_flip($fieldsForAppilication);
      }
      else {
        $propertyDataType = $property->getDataType();
        $fieldsForAppilication = $property->getSetting('fieldsForApplication') ?? [];
        $keysToExtract = array_flip($fieldsForAppilication);
      }

      $groupName = $property->getSetting('budgetGroupName') ?? 'general';

      // If found, copy from default component values.
      switch ($propertyDataType) {
        case 'grants_budget_income_static';
          $retVal[$propertyKey] = array_intersect_key(
            $dataFromDocument['incomeRowsArrayStatic'][$groupName][0] ?? [],
            $keysToExtract,
          );
          break;

        case 'grants_budget_cost_static';
          $retVal[$propertyKey] = array_intersect_key(
            $dataFromDocument['costRowsArrayStatic'][$groupName][0] ?? [],
            $keysToExtract,
          );
          break;

        case 'grants_budget_cost_other':
          $retVal[$propertyKey] = $dataFromDocument['budget_other_cost'][$groupName] ?? [];
          break;

        case 'grants_budget_income_other':
          $retVal[$propertyKey] = $dataFromDocument['budget_other_income'][$groupName] ?? [];
          break;

        default:
          continue;
      }
    }

    return $retVal;
  }

  /**
   * Process income/cost group name.
   */
  public static function processGroupName($property) {
    return $property->getValue();
  }

  /**
   * Process budget components to ATV structure.
   */
  public static function processBudgetInfo($property, $arguments) {
    $incomeStaticRow = [
      'general' => [
        'incomeRowsArrayStatic' => [],
        'otherIncomeRowsArrayStatic' => [],
      ],
    ];
    $costStaticRow = [
      'general' => [
        'costRowsArrayStatic' => [],
        'otherCostRowsArrayStatic' => [],
      ],
    ];

    foreach ($property as $propertyKey => $property) {
      $pDef = $property->getDataDefinition();
      $jsonPath = $pDef->getSetting('jsonPath');
      $pJsonPath = reset($jsonPath);
      $defaultValue = $pDef->getSetting('defaultValue');
      $valueCallback = $pDef->getSetting('fullItemValueCallback');
      $groupName = $pDef->getSetting('budgetGroupName') ?? 'general';
      $itemTypes = AtvSchema::getJsonTypeForDataType($pDef);
      $itemValue = AtvSchema::getItemValue($itemTypes, $property, $defaultValue, $valueCallback);
      $processedValues = [];
      if (isset($arguments['webform'])) {
        $processedValues = self::processMetaFields(
          $property,
          $propertyKey,
          $itemValue,
          $arguments['webform']
        );
      }

      switch ($pJsonPath) {
        case 'incomeRowsArrayStatic':
        case 'otherIncomeRowsArrayStatic':
          if (is_array($itemValue)) {
            $original = $incomeStaticRow[$groupName][$pJsonPath] ?? [];
            $incomeStaticRow[$groupName][$pJsonPath] = array_merge($original, $processedValues);
          }
          break;

        case 'costRowsArrayStatic':
        case 'otherCostRowsArrayStatic':
          if (is_array($itemValue)) {
            $original = $costStaticRow[$groupName][$pJsonPath] ?? [];
            $costStaticRow[$groupName][$pJsonPath] = array_merge($original, $processedValues);
          }
          break;
      }
    }

    foreach ($incomeStaticRow as $key => &$incomeRow) {
      $incomeRow['incomeGroupName'] = $key;
    }

    foreach ($costStaticRow as $key => &$costRow) {
      $costRow['costGroupName'] = $key;
    }

    return [
      'compensation' => [
        'budgetInfo' => [
          'incomeGroupsArrayStatic' => array_values($incomeStaticRow),
          'costGroupsArrayStatic' => array_values($costStaticRow),
        ],
      ],
    ];

  }

  /**
   * Add meta fields to budget component values.
   */
  private static function processMetaFields($propertyDefinition, $propertyKey, $values, $webform) {
    if (!is_array($values) || count($values) == 0 || !$webform) {
      return $values;
    }

    $webformMainElement = $webform->getElement($propertyKey);
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

    foreach ($values as &$value) {

      $fieldId = $value['ID'];

      $webformLabelElement = $webformMainElement['#webform_composite_elements'][$fieldId] ?? $webformMainElement['#webform_key'];
      $label = $webformLabelElement['#title'] ?? $webformMainElement['#title'];

      $element = [
        'label' => $label,
      ];

      $value['meta'] = json_encode(AtvSchema::getMetaData($page, $section, $element), JSON_UNESCAPED_UNICODE);

    }

    return $values;

  }

}

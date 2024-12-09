<?php

namespace Drupal\grants_metadata;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Provide a DocumentValueExtractorClass.
 *
 * This class is used for getting values from
 * ATV document.
 */
class DocumentValueExtractor {

  /**
   * Retrieves the value from document.
   *
   * @param array $content
   *   The decoded JSON content of the document.
   * @param array $pathArray
   *   The path in the JSON document from definition settings.
   * @param string $elementName
   *   The element name in the JSON.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|null $definition
   *   The data definition setup.
   *
   * @return mixed
   *   The parsed typed data structure.
   */
  public static function getValueFromDocument(
    array $content,
    array $pathArray,
    string $elementName,
    ?DataDefinitionInterface $definition,
  ): mixed {
    $newKey = array_shift($pathArray);

    if (array_key_exists($newKey, $content)) {
      return self::drillDownToElement($content[$newKey], $pathArray, $elementName, $definition);
    }
    elseif (array_key_exists($elementName, $content)) {
      return self::processRootElement($content, $elementName, $definition);
    }
    elseif (AtvSchema::numericKeys($content)) {
      return self::processNumericKeys($content, $elementName, $definition);
    }

    return NULL;
  }

  /**
   * Recursively drills down to the desired element in the document content.
   *
   * @param array $content
   *   The current level of document content.
   * @param array $pathArray
   *   The remaining path in the JSON document.
   * @param string $elementName
   *   The element name to retrieve.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|null $definition
   *   The data definition setup.
   *
   * @return mixed
   *   The parsed typed data structure at the desired path.
   */
  protected static function drillDownToElement(
    array $content,
    array $pathArray,
    string $elementName,
    ?DataDefinitionInterface $definition,
  ): mixed {
    return self::getValueFromDocument($content, $pathArray, $elementName, $definition);
  }

  /**
   * Processes the root element in the document content.
   *
   * @param array $content
   *   The document content.
   * @param string $elementName
   *   The element name to retrieve.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|null $definition
   *   The data definition setup.
   *
   * @return mixed
   *   The parsed typed data structure for the root element.
   */
  protected static function processRootElement(
    array $content,
    string $elementName,
    ?DataDefinitionInterface $definition,
  ): mixed {
    $thisElement = $content[$elementName];
    $itemPropertyDefinitions = self::getItemPropertyDefinitions($definition);

    if (is_array($thisElement)) {
      return self::processArrayElement($content, $elementName, $itemPropertyDefinitions);
    }

    return $thisElement;
  }

  /**
   * Processes an array element in the document content.
   *
   * @param array $content
   *   The document content.
   * @param string $elementName
   *   The element name to retrieve.
   * @param array|null $itemPropertyDefinitions
   *   The property definitions for the element.
   *
   * @return array
   *   The parsed typed data structure for the array element.
   */
  protected static function processArrayElement(
    array $content,
    string $elementName,
    ?array $itemPropertyDefinitions,
  ): array {
    $retval = [];

    foreach ($content[$elementName] as $key => $value) {
      foreach ($value as $key2 => $v) {
        self::getArrayElementItemValue($v, $key, $key2, $itemPropertyDefinitions, $retval);
      }
    }

    return $retval;
  }

  /**
   * Gets the value of an item within an array element.
   *
   * @param mixed $v
   *   The item value to process.
   * @param mixed $key
   *   The key of the current array element.
   * @param mixed $key2
   *   The subkey of the current array element.
   * @param array|null $itemPropertyDefinitions
   *   The property definitions for the item.
   * @param array $retval
   *   The array to store the processed value.
   */
  protected static function getArrayElementItemValue(
    $v,
    $key,
    $key2,
    ?array $itemPropertyDefinitions,
    array &$retval,
  ) {
    $itemValue = NULL;
    if (is_array($v)) {
      $itemValue = self::processNestedArrayItem($v, $itemPropertyDefinitions);
      if (array_key_exists('value', $v)) {
        $meta = isset($v['meta']) ? json_decode($v['meta'], TRUE) : NULL;
        $value = InputmaskHandler::convertPossibleInputmaskValue(
          $itemValue ?? $v['value'],
          $meta ?? []
        );

        $retval[$key][$v['ID']] = htmlspecialchars_decode($value);
      }
      else {
        $retval[$key][$key2] = $itemValue ?? $v;
      }
    }
    else {
      $itemValue = self::extractSimpleItemValue($v, $itemPropertyDefinitions, $key2);
      $retval[$key][$key2] = $itemValue ?? $v;
    }
  }

  /**
   * Processes a nested array item.
   *
   * @param array $item
   *   The nested array item to process.
   * @param array|null $itemPropertyDefinitions
   *   The property definitions for the item.
   *
   * @return mixed
   *   The processed item value.
   */
  protected static function processNestedArrayItem(
    array $item,
    ?array $itemPropertyDefinitions,
  ): mixed {
    if (isset($item['ID']) && is_array($itemPropertyDefinitions) && isset($itemPropertyDefinitions[$item['ID']])) {
      return self::extractItemValue($item, $itemPropertyDefinitions[$item['ID']]);
    }
    return NULL;
  }

  /**
   * Extracts the value of an item based on its property definitions.
   *
   * @param array $item
   *   The item to extract the value from.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $itemPropertyDefinition
   *   The property definition for the item.
   *
   * @return string|null
   *   The extracted item value.
   */
  protected static function extractItemValue(
    array $item,
    DataDefinitionInterface $itemPropertyDefinition,
  ): ?string {
    $valueExtractorConfig = $itemPropertyDefinition->getSetting('webformValueExtracter');
    if ($valueExtractorConfig) {
      $valueExtractorService = self::getDynamicService($valueExtractorConfig['service']);
      $method = $valueExtractorConfig['method'];
      return $valueExtractorService->$method($item);
    }
    return NULL;
  }

  /**
   * Extracts the value of a simple item.
   *
   * @param mixed $item
   *   The item to extract the value from.
   * @param array|null $itemPropertyDefinitions
   *   The property definitions for the item.
   * @param string|int $key
   *   The key for the current item.
   *
   * @return string|null
   *   The extracted item value.
   */
  protected static function extractSimpleItemValue(
    $item,
    ?array $itemPropertyDefinitions,
    $key,
  ): ?string {
    if (is_array($itemPropertyDefinitions) && isset($itemPropertyDefinitions[$key])) {
      $itemPropertyDefinition = $itemPropertyDefinitions[$key];
      $valueExtractorConfig = $itemPropertyDefinition->getSetting('webformValueExtracter');
      if ($valueExtractorConfig) {
        $valueExtractorService = self::getDynamicService($valueExtractorConfig['service']);
        $method = $valueExtractorConfig['method'];
        return $valueExtractorService->$method($item);
      }
    }
    return NULL;
  }

  /**
   * Processes numeric keys in the document content.
   *
   * @param array $content
   *   The document content.
   * @param string $elementName
   *   The element name to retrieve.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|null $definition
   *   The data definition setup.
   *
   * @return mixed
   *   The parsed typed data structure for the numeric keys.
   */
  protected static function processNumericKeys(
    array $content,
    string $elementName,
    ?DataDefinitionInterface $definition,
  ): mixed {
    foreach ($content as $value) {
      if (!is_array($value)) {
        return $value;
      }

      if ($value['ID'] == $elementName) {
        return self::parseElementValue($value, $definition);
      }
    }

    return NULL;
  }

  /**
   * Parses the value of an element.
   *
   * @param array $value
   *   The element value to parse.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|null $definition
   *   The data definition setup.
   *
   * @return mixed
   *   The parsed element value.
   */
  protected static function parseElementValue(
    array $value,
    ?DataDefinitionInterface $definition,
  ): mixed {
    $parseValue = is_string($value['value']) ? $value['value'] : '';
    $meta = isset($value['meta']) ? json_decode($value['meta'], TRUE) : NULL;

    $retval = htmlspecialchars_decode($parseValue);
    if ($definition->getDataType() == 'boolean') {
      $retval = self::parseBooleanValue($retval);
    }

    $retval = InputmaskHandler::convertPossibleInputmaskValue($retval, $meta);

    $valueExtractorConfig = $definition->getSetting('webformValueExtracter');
    if ($valueExtractorConfig) {
      $valueExtractorService = self::getDynamicService($valueExtractorConfig['service']);
      $method = $valueExtractorConfig['method'];
      $retval = $valueExtractorService->$method($retval);
    }

    return $retval;
  }

  /**
   * Parses a boolean value.
   *
   * @param string $value
   *   The value to parse.
   *
   * @return string
   *   The parsed boolean value.
   */
  protected static function parseBooleanValue(string $value): string {
    if ($value == 'true') {
      return '1';
    }
    elseif ($value == 'false') {
      return '0';
    }
    return '0';
  }

  /**
   * Retrieves item property definitions from a data definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|null $definition
   *   The data definition setup.
   *
   * @return array|null
   *   The property definitions for the item, or NULL if none exist.
   */
  protected static function getItemPropertyDefinitions(
    ?DataDefinitionInterface $definition,
  ): ?array {
    if (method_exists($definition, 'getItemDefinition')) {
      $itemDefinition = $definition->getItemDefinition();
      if ($itemDefinition !== NULL) {
        return $itemDefinition->getPropertyDefinitions();
      }
    }

    if (method_exists($definition, 'getPropertyDefinitions')) {
      return $definition->getPropertyDefinitions();
    }

    return NULL;
  }

  /**
   * Retrieves a dynamic service based on the service name.
   *
   * @param string $serviceName
   *   The name of the service.
   *
   * @return object
   *   The dynamic service.
   */
  protected static function getDynamicService(string $serviceName): object {
    return \Drupal::service($serviceName);
  }

}

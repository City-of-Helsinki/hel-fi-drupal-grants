<?php

namespace Drupal\grants_metadata;

/**
 * Provides a PropertySchema class.
 *
 * This class is used for parsing the schema of
 * an element. The schema is originally loaded from
 * tietoliikennesanoma_schema.json.
 */
class PropertySchema {

  /**
   * The getPropertySchema method.
   *
   * This method attempts to find the schema definition
   * for single element inside the "tietoliikennesanoma_schema.json" file.
   * The method calls getSubPropertySchema() if the element we are looking
   * for is not found on the first level of the structure.
   *
   * NOTE: The functionality of this method and its helper methods mimic
   * the functionality of the old getPropertySchema() method before refactoring
   * took place. As of 09/2023, this method does not correctly return the schema
   * for all requested elements, since the old implementation was broken. My
   * assumption is that typedDataToDocumentContentWithWebform() works
   * incorrectly and needs to get fixed before getPropertySchema()
   * can be fixed.
   *
   * @param string $elementName
   *   The name of the element we are trying to find the schema for.
   * @param array $structure
   *   The full schema structure.
   *
   * @return array|null
   *   The schema for given property or null.
   */
  public static function getPropertySchema(string $elementName, array $structure): ?array {
    foreach ($structure['properties'] as $topLevelProperty) {
      if ($topLevelProperty['type'] === 'object') {
        if (array_key_exists($elementName, $topLevelProperty['properties'])) {
          return $topLevelProperty['properties'][$elementName];
        }
        return self::getSubPropertySchema($elementName, $topLevelProperty);
      }
    }
    return NULL;
  }

  /**
   * The getSubPropertySchema method.
   *
   * This method loops through the properties on the second level
   * of the whole schema structure. The method attempts to find
   * the element inside nested arrays and objects.
   *
   * @param string $elementName
   *   The name of the element we are trying to find the schema for.
   * @param array $topLevelProperty
   *   A top level property of the full schema structure.
   *
   * @return array|null
   *   The schema for given property or null.
   */
  protected static function getSubPropertySchema(string $elementName, array $topLevelProperty): ?array {
    foreach ($topLevelProperty['properties'] as $subProperty) {
      if ($schema = self::isElementInSubArray($elementName, $subProperty)) {
        break;
      }
      if ($schema = self::isElementInSubObject($elementName, $subProperty)) {
        break;
      }
      // This part makes zero sense, but the old code did this, so we do it too.
      // Essentially this returns the schema of the "additionalInformation"
      // property, which has nothing to do with any elements.
      if ($schema = self::isPropertyString($subProperty)) {
        break;
      }
    }
    return $schema ?? NULL;
  }

  /**
   * The isElementInSubObject method.
   *
   * This method attempts to locate the schema for a given
   * element that is found inside a property of the type object.
   * The isElementInSubArray() method is called if the name of the
   * element is not located inside the properties of the object.
   *
   * @param string $elementName
   *   The name of the element we are trying to find the schema for.
   * @param array $property
   *   The property we are checking.
   *
   * @return array|null
   *   The schema for given property or null.
   */
  protected static function isElementInSubObject(string $elementName, array $property): ?array {
    if ($property['type'] === 'object') {
      if (array_key_exists($elementName, $property['properties'])) {
        return $property['properties'][$elementName];
      }
      foreach ($property['properties'] as $subProperty) {
        if ($schema = self::isElementInSubArray($elementName, $subProperty)) {
          return $schema;
        }
      }
    }
    return NULL;
  }

  /**
   * The isElementInSubArray method.
   *
   * This method attempts to locate the schema for a given
   * element that is found inside a property of the type array.
   * The method checks whether the arrays items are of the type
   * array or object, and uses the isElementInEnum() method accordingly.
   *
   * @param string $elementName
   *   The name of the element we are trying to find the schema for.
   * @param array $property
   *   The property we are checking.
   *
   * @return array|null
   *   The schema for given property or null.
   */
  protected static function isElementInSubArray(string $elementName, array $property): ?array {
    if ($property['type'] === 'array') {
      $itemsType = $property['items']['type'];

      if ($itemsType === 'object' && self::isElementInEnum($property['items']['properties'], $elementName)) {
        return $property['items'];
      }
      if ($itemsType === 'array' && self::isElementInEnum($property['items']['items']['properties'], $elementName)) {
        return $property['items']['items'];
      }
    }
    return NULL;
  }

  /**
   * The isPropertyString method.
   *
   * This method checks if a property is of the type string,
   * and returns said property if true.
   *
   * @param array $property
   *   The property we are checking.
   *
   * @return array|null
   *   The schema for given property or null.
   */
  protected static function isPropertyString(array $property): ?array {
    if ($property['type'] === 'string') {
      return $property;
    }
    return NULL;
  }

  /**
   * The isElementInEnum method.
   *
   * This method is used by the isElementInSubArray() method to
   * check whether the element we are looking for is located
   * inside an "enum" array of a property.
   *
   * @param array $property
   *   The property we are checking.
   * @param string $elementName
   *   The name of the element we are trying to find the schema for.
   *
   * @return bool
   *   True if elementName is in the enum array, false otherwise.
   */
  protected static function isElementInEnum(array $property, string $elementName): bool {
    return isset($property['ID']['enum']) &&
      is_array($property['ID']['enum']) &&
      in_array($elementName, $property['ID']['enum']);
  }

}

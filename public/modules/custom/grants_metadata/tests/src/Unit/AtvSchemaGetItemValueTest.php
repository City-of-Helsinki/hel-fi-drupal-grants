<?php

use Drupal\grants_metadata\AtvSchema;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the getItemValue method in the AtvSchema class.
 */
class AtvSchemaGetItemValueTest extends TestCase {

  /**
   * Tests the case when the item value is null with a default value provided.
   */
  public function testNullItemValueWithDefault() {
    $itemTypes = ['dataType' => 'string', 'jsonType' => 'string'];
    $itemValue = NULL;
    $defaultValue = 'default';
    $valueCallback = NULL;

    $result = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
    $this->assertEquals('default', $result);
  }

  /**
   * Tests the case when the item value is an integer and needs to be converted to a string.
   */
  public function testItemValueAsString() {
    $itemTypes = ['dataType' => 'string', 'jsonType' => 'string'];
    $itemValue = 123;
    $defaultValue = NULL;
    $valueCallback = NULL;

    $result = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
    $this->assertEquals('123', $result);
  }

  /**
   * Tests the case when the item value is boolean true and needs to be converted to the string "true".
   */
  public function testBooleanTrueToString() {
    $itemTypes = ['dataType' => 'string', 'jsonType' => 'bool'];
    $itemValue = TRUE;
    $defaultValue = NULL;
    $valueCallback = NULL;

    $result = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
    $this->assertEquals('true', $result);
  }

  /**
   * Tests the case when the item value is boolean false and needs to be converted to the string "false".
   */
  public function testBooleanFalseToString() {
    $itemTypes = ['dataType' => 'string', 'jsonType' => 'bool'];
    $itemValue = FALSE;
    $defaultValue = NULL;
    $valueCallback = NULL;

    $result = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
    $this->assertEquals('false', $result);
  }

  /**
   * Tests the case when the item value is a string representing an integer with underscores.
   */
  public function testIntJsonType() {
    $itemTypes = ['dataType' => 'string', 'jsonType' => 'int'];
    $itemValue = '1_000';
    $defaultValue = NULL;
    $valueCallback = NULL;

    $result = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
    $this->assertEquals('1000', $result);
  }

  /**
   * Tests the case when the item value is the string "Yes" and needs to be converted to the string "true".
   */
  public function testBooleanStringYes() {
    $itemTypes = ['dataType' => 'string', 'jsonType' => 'bool'];
    $itemValue = 'Yes';
    $defaultValue = NULL;
    $valueCallback = NULL;

    $result = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
    $this->assertEquals('true', $result);
  }

  /**
   * Tests the case when the item value is the string "No" and needs to be converted to the string "false".
   */
  public function testBooleanStringNo() {
    $itemTypes = ['dataType' => 'string', 'jsonType' => 'bool'];
    $itemValue = 'No';
    $defaultValue = NULL;
    $valueCallback = NULL;

    $result = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
    $this->assertEquals('false', $result);
  }

  /**
   * Tests the case when the item value is the string "0" and needs to be converted to the string "false".
   */
  public function testZeroStringToFalse() {
    $itemTypes = ['dataType' => 'string', 'jsonType' => 'bool'];
    $itemValue = '0';
    $defaultValue = NULL;
    $valueCallback = NULL;

    $result = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
    $this->assertEquals('false', $result);
  }

  /**
   * Tests the case when the item value is the string "1" and needs to be converted to the string "true".
   */
  public function testOneStringToTrue() {
    $itemTypes = ['dataType' => 'string', 'jsonType' => 'bool'];
    $itemValue = '1';
    $defaultValue = NULL;
    $valueCallback = NULL;

    $result = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
    $this->assertEquals('true', $result);
  }

  /**
   * Tests the case when both the item value and default value are null.
   */
  public function testDefaultValueNotSet() {
    $itemTypes = ['dataType' => 'string', 'jsonType' => 'string'];
    $itemValue = NULL;
    $defaultValue = NULL;
    $valueCallback = NULL;

    $result = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
    $this->assertEquals('', $result);
  }

}

<?php

namespace Drupal\Tests\grants_metadata\Unit;

use Drupal\grants_metadata\AtvSchema;
use Drupal\Tests\UnitTestCase;

/**
 * Tests AtvSchema class.
 *
 * @covers \Drupal\grants_metadata\AtvSchema
 * @group grants_metadata
 */
class AtvSchemaGetItemValueTest extends UnitTestCase {

  /**
   * Test null item value with a default value.
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
   * Test item value as integer converted to string.
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
   * Test boolean true converted to string "true".
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
   * Test boolean false converted to string "false".
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
   * Test integer json type with underscores removed.
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
   * Test string "Yes" converted to "true".
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
   * Test string "No" converted to "false".
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
   * Test string "0" converted to "false".
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
   * Test string "1" converted to "true".
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
   * Test null item value with no default value.
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

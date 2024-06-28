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
class AtvSchemaExtractDataTest extends UnitTestCase {

  /**
   * Test extractDataForWebForm with a simple non-nested compensation array.
   */
  public function testExtractDataForWebFormSimple() {
    $content = [
      'compensation' => [
        ['ID' => 'key1', 'value' => 'value1'],
        ['ID' => 'key2', 'value' => 'value2'],
      ],
    ];
    $keys = ['key1', 'key2'];
    $expected = [
      'key1' => 'value1',
      'key2' => 'value2',
    ];

    $result = AtvSchema::extractDataForWebForm($content, $keys);

    $this->assertEquals($expected, $result);
  }

  /**
   * Test extractDataForWebForm with nested compensation array.
   */
  public function testExtractDataForWebFormNested() {
    $content = [
      'compensation' => [
        'section1' => [
          ['ID' => 'key1', 'value' => 'value1'],
          ['ID' => 'key2', 'value' => 'value2'],
        ],
        'section2' => [
          'subsection' => [
            ['ID' => 'key3', 'value' => 'value3'],
            ['ID' => 'key4', 'value' => 'value4'],
          ],
        ],
      ],
    ];
    $keys = ['key1', 'key3'];
    $expected = [
      'key1' => 'value1',
      'key3' => 'value3',
    ];

    $result = AtvSchema::extractDataForWebForm($content, $keys);

    $this->assertEquals($expected, $result);
  }

  /**
   * Test extractDataForWebForm with deeply nested compensation array.
   */
  public function testExtractDataForWebFormDeeplyNested() {
    $content = [
      'compensation' => [
        'level1' => [
          'level2' => [
            'level3' => [
              ['ID' => 'key1', 'value' => 'value1'],
              ['ID' => 'key2', 'value' => 'value2'],
            ],
          ],
        ],
      ],
    ];
    $keys = ['key1'];
    $expected = [
      'key1' => 'value1',
    ];

    $result = AtvSchema::extractDataForWebForm($content, $keys);

    $this->assertEquals($expected, $result);
  }

  /**
   * Test extractDataForWebForm with mixed nested and non-nested items.
   */
  public function testExtractDataForWebFormMixed() {
    $content = [
      'compensation' => [
        ['ID' => 'key1', 'value' => 'value1'],
        'section' => [
          ['ID' => 'key2', 'value' => 'value2'],
        ],
        'key3' => 'value3',
      ],
    ];
    $keys = ['key1', 'key2', 'key3'];
    $expected = [
      'key1' => 'value1',
      'key2' => 'value2',
      'key3' => 'value3',
    ];

    $result = AtvSchema::extractDataForWebForm($content, $keys);

    $this->assertEquals($expected, $result);
  }

  /**
   * Test extractDataForWebForm with missing keys.
   */
  public function testExtractDataForWebFormMissingKeys() {
    $content = [
      'compensation' => [
        ['ID' => 'key1', 'value' => 'value1'],
        ['ID' => 'key2', 'value' => 'value2'],
      ],
    ];
    $keys = ['key3'];
    $expected = [];

    $result = AtvSchema::extractDataForWebForm($content, $keys);

    $this->assertEquals($expected, $result);
  }

  /**
   * Test extractDataForWebForm with empty compensation array.
   */
  public function testExtractDataForWebFormEmptyCompensation() {
    $content = [
      'compensation' => [],
    ];
    $keys = ['key1'];
    $expected = [];

    $result = AtvSchema::extractDataForWebForm($content, $keys);

    $this->assertEquals($expected, $result);
  }

  /**
   * Test extractDataForWebForm with missing compensation field.
   */
  public function testExtractDataForWebFormMissingCompensation() {
    $content = [];
    $keys = ['key1'];
    $expected = [];

    $result = AtvSchema::extractDataForWebForm($content, $keys);

    $this->assertEquals($expected, $result);
  }

}

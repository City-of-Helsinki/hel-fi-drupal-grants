<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit\Form;

use Drupal\grants_application\Mapper\JsonHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Tests JsonHandler custom handler methods directly.
 *
 * @group grants_application
 */
final class JsonHandlerTest extends UnitTestCase {

  /**
   * Tests setLabelAndValue with a valid 2-element array.
   */
  public function testSetLabelAndValueWithValidData(): void {
    $definition = [
      'data' => [
        'ID' => 'budget_item',
        'label' => '',
        'value' => '',
        'valueType' => 'double',
      ],
    ];

    $result = JsonHandler::setLabelAndValue(['Toimitilat', '5000'], $definition);

    $this->assertEquals('Toimitilat', $result['label']);
    $this->assertEquals('5000', $result['value']);
    $this->assertEquals('budget_item', $result['ID']);
  }

  /**
   * Tests income maps multiple items and appends index to ID.
   */
  public function testIncomeWithMultipleItems(): void {
    $definition = [
      'data' => [
        'ID' => 'muut_menot',
        'label' => '',
        'value' => '',
        'valueType' => 'double',
      ],
    ];
    $data = [
      ['Vuokra', '1000'],
      ['Henkilöstö', '2000'],
    ];

    $result = JsonHandler::income($data, $definition);

    $this->assertIsArray($result);
    $this->assertCount(2, $result);
    $this->assertStringEndsWith('_0', $result[0]['ID']);
    $this->assertStringEndsWith('_1', $result[1]['ID']);
    $this->assertEquals('Vuokra', $result[0]['label']);
    $this->assertEquals('2000', $result[1]['value']);
  }

  /**
   * Tests enumToLabel returns the mapped label for a known key.
   */
  public function testEnumToLabelWithKnownKey(): void {
    $definition = [
      'value_map' => [
        '1' => 'Alle vuosi',
        '2' => '1-3 vuotta',
        '3' => 'Yli 3 vuotta',
      ],
    ];

    $this->assertEquals('1-3 vuotta', JsonHandler::enumToLabel('2', $definition));
  }

  /**
   * Tests enumToLabel maps boolean true ("1") to the configured label.
   */
  public function testEnumToLabelBoolTrue(): void {
    $definition = [
      'value_map' => [
        '1' => 'Kyllä',
        '' => 'Ei',
      ],
    ];

    $this->assertEquals('Kyllä', JsonHandler::enumToLabel('1', $definition));
  }

  /**
   * Tests enumToLabel maps boolean false ("") to the configured label.
   */
  public function testEnumToLabelBoolFalse(): void {
    $definition = [
      'value_map' => [
        '1' => 'Kyllä',
        '' => 'Ei',
      ],
    ];

    $this->assertEquals('Ei', JsonHandler::enumToLabel('', $definition));
  }

  /**
   * Tests handleDefinitionUpdate dispatches to the named method.
   */
  public function testHandleDefinitionUpdateDispatch(): void {
    $definition = [
      'value_map' => ['1' => 'Yksi'],
    ];

    $result = (new JsonHandler())->handleDefinitionUpdate('enumToLabel', '1', $definition);

    $this->assertEquals('Yksi', $result);
  }

}

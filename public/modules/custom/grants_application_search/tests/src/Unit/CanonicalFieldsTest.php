<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application_search\Unit;

use Drupal\grants_application_search\Plugin\search_api\processor\CanonicalFields;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;

/**
 * Unit test for CanonicalFields processor.
 *
 * @group grants_application_search
 */
final class CanonicalFieldsTest extends CanonicalUnitTestBase {

  /**
   * Tests React form side with canonical ID mapping.
   */
  public function testReactApplication(): void {
    $target_fields = [];
    $processor = $this->createProcessor($target_fields);

    $fields = [
      'field_react_form' => $this->sourceField(['react']),
      'application_subvention_type' => $this->sourceField(['1', '2', '2']),
      'applicant_types' => $this->sourceField(['A', 'B']),
      'application_target_group' => $this->sourceField(['tg1']),

      'canonical_subvention_type' => $target_fields['canonical_subvention_type'],
      'canonical_applicant_type' => $target_fields['canonical_applicant_type'],
      'canonical_target_group' => $target_fields['canonical_target_group'],
    ];

    $item = $this->createMock(ItemInterface::class);
    $item->method('getFields')->willReturn($fields);

    $processor->addFieldValues($item);

    $this->assertSame(['1', '2'], $target_fields['canonical_subvention_type']->values);
    $this->assertSame(['A', 'B'], $target_fields['canonical_applicant_type']->values);
    $this->assertSame(['tg1'], $target_fields['canonical_target_group']->values);
  }

  /**
   * Tests webform side with canonical ID mapping.
   */
  public function testWebformApplication(): void {
    $target_fields = [];
    $processor = $this->createProcessor($target_fields);

    $fields = [
      'field_avustuslaji' => $this->sourceField(['45', '46', '46']),
      'field_hakijatyyppi' => $this->sourceField(['X', 'Y']),
      'field_target_group' => $this->sourceField(['tgX']),

      'canonical_subvention_type' => $target_fields['canonical_subvention_type'],
      'canonical_applicant_type' => $target_fields['canonical_applicant_type'],
      'canonical_target_group' => $target_fields['canonical_target_group'],
    ];

    $item = $this->createMock(ItemInterface::class);
    $item->method('getFields')->willReturn($fields);

    $processor->addFieldValues($item);

    $this->assertSame(['1', '2'], $target_fields['canonical_subvention_type']->values);
    $this->assertSame(['X', 'Y'], $target_fields['canonical_applicant_type']->values);
    $this->assertSame(['tgX'], $target_fields['canonical_target_group']->values);
  }

  /**
   * Tests handling of empty and null values.
   */
  public function testEmptyValues(): void {
    $target_fields = [];
    $processor = $this->createProcessor($target_fields);

    $fields = [
      'field_avustuslaji' => $this->sourceField(['']),
      'field_hakijatyyppi' => $this->sourceField([]),
      'field_target_group' => $this->sourceField([]),

      'canonical_subvention_type' => $target_fields['canonical_subvention_type'],
      'canonical_applicant_type' => $target_fields['canonical_applicant_type'],
      'canonical_target_group' => $target_fields['canonical_target_group'],
    ];

    $item = $this->createMock(ItemInterface::class);
    $item->method('getFields')->willReturn($fields);

    $processor->addFieldValues($item);

    $this->assertSame([], $target_fields['canonical_subvention_type']->values);
    $this->assertSame([], $target_fields['canonical_applicant_type']->values);
    $this->assertSame([], $target_fields['canonical_target_group']->values);
  }

  /**
   * Creates processor instance with mocked fields helper.
   *
   * @param array $target_fields
   *   Returned by reference; populated with canonical target field stubs.
   *
   * @return \Drupal\grants_application_search\Plugin\search_api\processor\CanonicalFields
   *   Returns the processor.
   */
  private function createProcessor(array &$target_fields): CanonicalFields {
    $processor = new CanonicalFields([], 'id', []);

    $target_fields = [
      'canonical_subvention_type' => $this->targetField(),
      'canonical_applicant_type' => $this->targetField(),
      'canonical_target_group' => $this->targetField(),
    ];

    $fields_helper = $this->createMock(FieldsHelperInterface::class);
    $fields_helper
      ->method('filterForPropertyPath')
      ->willReturnCallback(
        static function (array $fields, $datasource_id, string $property_path) use (&$target_fields): array {
          return isset($target_fields[$property_path])
            ? [$target_fields[$property_path]]
            : [];
        }
      );

    $this->setProtectedProperty($processor, 'fieldsHelper', $fields_helper);

    return $processor;
  }

  /**
   * Simple source field stub.
   *
   * Creates a mock source field object that returns predefined values,
   * simulating Drupal field objects for testing purposes.
   *
   * @param array $values
   *   The values to return from the getValues() method.
   *
   * @return object
   *   A mock field object with the specified values.
   */
  private function sourceField(array $values): object {
    return new class($values) {
      // phpcs:disable
      public function __construct(private readonly array $values) {}

      public function getValues(): array {
        return $this->values;
      }
      // phpcs:enable
    };
  }

  /**
   * Simple target field stub.
   *
   * Creates a mock target field object that collects values via addValue(),
   * simulating the canonical field objects that receive mapped values.
   *
   * @return object
   *   A mock field object with a values array and addValue() method.
   */
  private function targetField(): object {
    return new class() {
      // phpcs:disable
      public array $values = [];

      public function addValue(mixed $value): void {
        $this->values[] = (string) $value;
      }
      // phpcs:enable
    };
  }

}

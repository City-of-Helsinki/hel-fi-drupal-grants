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
   * Tests that an empty field_react_form falls back to the webform sources.
   */
  public function testEmptyReactFormFallsBackToWebform(): void {
    $target_fields = [];
    $processor = $this->createProcessor($target_fields);

    $fields = [
      // If field_react_form value is available but empty, it should not
      // be treated as a React form.
      'field_react_form' => $this->sourceField(['']),
      // Webform sources should be used instead.
      'field_avustuslaji' => $this->sourceField(['45', '46']),
      'field_hakijatyyppi' => $this->sourceField(['X', 'Y']),
      'field_target_group' => $this->sourceField(['tgX']),
      // React sources are present but must be ignored.
      'application_subvention_type' => $this->sourceField(['1']),
      'applicant_types' => $this->sourceField(['A']),
      'application_target_group' => $this->sourceField(['tg1']),

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
   * Tests that an unmapped webform subvention value passes through unchanged.
   */
  public function testUnmappedSubventionValuePassesThrough(): void {
    $target_fields = [];
    $processor = $this->createProcessor($target_fields);

    $fields = [
      // The subvention '45' is mapped to '1', but '9999' is not in the map
      // so it should be kept as '9999'.
      'field_avustuslaji' => $this->sourceField(['45', '9999']),
      'canonical_subvention_type' => $target_fields['canonical_subvention_type'],
      'canonical_applicant_type' => $target_fields['canonical_applicant_type'],
      'canonical_target_group' => $target_fields['canonical_target_group'],
    ];

    $item = $this->createMock(ItemInterface::class);
    $item->method('getFields')->willReturn($fields);

    $processor->addFieldValues($item);

    $this->assertSame(['1', '9999'], $target_fields['canonical_subvention_type']->values);
  }

  /**
   * Tests that a canonical target field absent from the index is skipped.
   */
  public function testMissingCanonicalTargetFieldIsSkipped(): void {
    $target_fields = [];
    $processor = $this->createProcessor($target_fields);
    $orphan_subvention = $target_fields['canonical_subvention_type'];
    unset($target_fields['canonical_subvention_type']);

    $fields = [
      'field_avustuslaji' => $this->sourceField(['45']),
      'field_hakijatyyppi' => $this->sourceField(['X']),
      'field_target_group' => $this->sourceField(['tgX']),
      'canonical_applicant_type' => $target_fields['canonical_applicant_type'],
      'canonical_target_group' => $target_fields['canonical_target_group'],
    ];

    $item = $this->createMock(ItemInterface::class);
    $item->method('getFields')->willReturn($fields);

    $processor->addFieldValues($item);

    // The missing target field was skipped: it received no values.
    $this->assertSame([], $orphan_subvention->values);
    // The remaining canonical fields are still populated.
    $this->assertSame(['X'], $target_fields['canonical_applicant_type']->values);
    $this->assertSame(['tgX'], $target_fields['canonical_target_group']->values);
  }

  /**
   * Tests that a source field absent from the item is skipped.
   */
  public function testMissingSourceFieldIsSkipped(): void {
    $target_fields = [];
    $processor = $this->createProcessor($target_fields);

    // The subvention field "field_avustuslaji" is deliberately omitted.
    $fields = [
      'field_hakijatyyppi' => $this->sourceField(['X', 'Y']),
      'field_target_group' => $this->sourceField(['tgX']),

      'canonical_subvention_type' => $target_fields['canonical_subvention_type'],
      'canonical_applicant_type' => $target_fields['canonical_applicant_type'],
      'canonical_target_group' => $target_fields['canonical_target_group'],
    ];

    $item = $this->createMock(ItemInterface::class);
    $item->method('getFields')->willReturn($fields);

    $processor->addFieldValues($item);

    // Source field missing: the canonical field received no values.
    $this->assertSame([], $target_fields['canonical_subvention_type']->values);
    // The fields with present sources are still populated.
    $this->assertSame(['X', 'Y'], $target_fields['canonical_applicant_type']->values);
    $this->assertSame(['tgX'], $target_fields['canonical_target_group']->values);
  }

  /**
   * Tests that selfMap() drops empty values.
   */
  public function testSelfMapSkipsEmptyValues(): void {
    $processor = new CanonicalFields([], 'id', []);
    $result = $this->invokeProtected($processor, 'selfMap', [['1', '', '2']]);
    $this->assertSame(['1' => '1', '2' => '2'], $result);
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
      /** @param array<mixed> $values */
      public function __construct(private readonly array $values) {}

      /** @return array<mixed> */
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
      /** @var array<string> */
      public array $values = [];

      public function addValue(mixed $value): void {
        $this->values[] = (string) $value;
      }
      // phpcs:enable
    };
  }

}

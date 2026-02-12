<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Plugin\views\filter;

/**
 * Test override for options_allowed_values().
 *
 * The production code calls options_allowed_values() unqualified, so PHP will
 * resolve this namespaced function before the global one.
 */
function options_allowed_values($definition): array {
  return [
    1 => 'One',
    'two' => 'Two',
  ];
}

namespace Drupal\Tests\grants_application_search\Unit;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\grants_application_search\Plugin\views\filter\CanonicalApplicantType;

/**
 * Tests the applicant type filter.
 *
 * @group grants_application_search
 */
final class CanonicalApplicantTypeTest extends CanonicalUnitTestBase {

  /**
   * Tests that buildSelectOptions() returns proper string key-value pairs.
   */
  public function testBuildSelectOptionsReturnsStringKeyValuePairs(): void {
    $storageDefinition = $this->createMock(FieldStorageDefinitionInterface::class);

    $entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $entityFieldManager->method('getFieldStorageDefinitions')
      ->with('node')
      ->willReturn([
        'field_hakijatyyppi' => $storageDefinition,
      ]);

    $filter = new CanonicalApplicantType([], 'id', [], $entityFieldManager);

    $options = $this->invokeProtected($filter, 'buildSelectOptions');

    $this->assertSame([
      '1' => 'One',
      'two' => 'Two',
    ], $options);
  }

  /**
   * Tests that buildSelectOptions() returns empty array when field is missing.
   */
  public function testBuildSelectOptionsReturnsEmptyWhenFieldMissing(): void {
    $entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $entityFieldManager->method('getFieldStorageDefinitions')->with('node')->willReturn([]);

    $filter = new CanonicalApplicantType([], 'id', [], $entityFieldManager);

    $options = $this->invokeProtected($filter, 'buildSelectOptions');
    $this->assertSame([], $options);
  }

}

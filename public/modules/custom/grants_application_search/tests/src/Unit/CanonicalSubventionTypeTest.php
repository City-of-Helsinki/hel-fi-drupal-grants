<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application_search\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\grants_application_search\Plugin\views\filter\CanonicalSubventionType;

/**
 * Tests subvention type filter.
 *
 * @group grants_application_search
 */
final class CanonicalSubventionTypeTest extends CanonicalUnitTestBase {

  /**
   * Tests that getOptionValue() maps term IDs to canonical IDs.
   */
  public function testGetOptionValueMapsTermIdToCanonicalId(): void {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $languageManager = $this->createMock(LanguageManagerInterface::class);

    $filter = new CanonicalSubventionType([], 'id', [], $entityTypeManager, $languageManager);

    $this->assertSame('1', $this->invokeProtected($filter, 'getOptionValue', ['45']));
    $this->assertSame('2', $this->invokeProtected($filter, 'getOptionValue', ['46']));
    $this->assertNull($this->invokeProtected($filter, 'getOptionValue', ['999999']));
  }

}

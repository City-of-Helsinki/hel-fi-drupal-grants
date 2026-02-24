<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application_search\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\grants_application_search\Plugin\views\filter\CanonicalTargetGroup;

/**
 * Tests target group filter.
 *
 * @group grants_application_search
 */
final class CanonicalTargetGroupTest extends CanonicalUnitTestBase {

  /**
   * Tests getter methods return expected configuration values.
   */
  public function testGetters(): void {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $languageManager = $this->createMock(LanguageManagerInterface::class);

    $filter = new CanonicalTargetGroup([], 'id', [], $entityTypeManager, $languageManager);

    $this->assertSame('canonical_target_group', $this->invokeProtected($filter, 'getCanonicalField'));
    $this->assertSame('target_group', $this->invokeProtected($filter, 'getVocabulary'));
  }

}

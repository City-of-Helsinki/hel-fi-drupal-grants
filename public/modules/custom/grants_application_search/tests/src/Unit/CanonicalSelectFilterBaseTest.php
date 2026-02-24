<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application_search\Unit;

use Drupal\grants_application_search\Plugin\views\filter\CanonicalSelectFilterBase;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api\Query\QueryInterface;

/**
 * Tests select filters base methods.
 *
 * @group grants_application_search
 */
final class CanonicalSelectFilterBaseTest extends CanonicalUnitTestBase {

  /**
   * Tests that getSelectOptions() caches results to avoid repeated builds.
   */
  public function testGetSelectOptionsCaches(): void {
    $filter = new class([], 'id', []) extends CanonicalSelectFilterBase {
      // phpcs:disable
      public int $buildCalls = 0;
      protected function getCanonicalField(): string {
        return 'canonical_field';
      }
      protected function buildSelectOptions(): array {
        $this->buildCalls++;
        /** @phpstan-ignore-next-line */
        return ['1' => 'One'];
      }
      // phpcs:enable
    };

    $first = $this->invokeProtected($filter, 'getSelectOptions');
    $second = $this->invokeProtected($filter, 'getSelectOptions');

    $this->assertSame(['1' => 'One'], $first);
    $this->assertSame(['1' => 'One'], $second);
    $this->assertSame(1, $filter->buildCalls);
  }

  /**
   * Tests that query() method no-ops when filter value is empty.
   */
  public function testQueryNoopsOnEmptyValue(): void {
    $filter = $this->createConcreteFilter();

    $this->setProtectedProperty($filter, 'value', NULL);
    $this->setProtectedProperty($filter, 'query', $this->createMock(SearchApiQuery::class));
    $filter->query();

    $this->setProtectedProperty($filter, 'value', '');
    $filter->query();

    $this->assertTrue(TRUE);
  }

  /**
   * Tests that query() method no-ops when not using SearchApiQuery.
   */
  public function testQueryNoopsWhenNotSearchApiQuery(): void {
    $filter = $this->createConcreteFilter();

    $this->setProtectedProperty($filter, 'value', 'x');
    $this->setProtectedProperty($filter, 'query', new \stdClass());
    $filter->query();

    $this->assertTrue(TRUE);
  }

  /**
   * Tests that query() method adds proper condition when value is set.
   */
  public function testQueryAddsCondition(): void {
    $filter = $this->createConcreteFilter();

    $search_api_inner_query = $this->createMock(QueryInterface::class);
    $search_api_inner_query
      ->expects(self::once())
      ->method('addCondition')
      ->with('canonical_field', ['abc'], 'IN');

    $views_search_api_query = $this->createMock(SearchApiQuery::class);
    $views_search_api_query
      ->method('getSearchApiQuery')
      ->willReturn($search_api_inner_query);

    $this->setProtectedProperty($filter, 'query', $views_search_api_query);
    $this->setProtectedProperty($filter, 'value', ['abc', 'def']);

    $filter->query();
  }

  /**
   * Creates a concrete filter implementation for testing.
   *
   * @return \Drupal\grants_application_search\Plugin\views\filter\CanonicalSelectFilterBase
   *   A concrete filter instance for testing.
   */
  private function createConcreteFilter(): CanonicalSelectFilterBase {
    return new class([], 'id', []) extends CanonicalSelectFilterBase {
      // phpcs:disable
      protected function getCanonicalField(): string {
        return 'canonical_field';
      }
      protected function buildSelectOptions(): array {
        return [];
      }
      // phpcs:enable
    };
  }

}

<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Plugin\views\filter;

use Drupal\grants_application_search\Plugin\search_api\processor\CanonicalFields;
use Drupal\views\Attribute\ViewsFilter;

/**
 * Canonical subvention type filter.
 *
 * @todo UHF-12853: Review this class when removing the webform functionality.
 */
#[ViewsFilter('canonical_subvention_type_select')]
final class CanonicalSubventionType extends CanonicalTaxonomySelectFilterBase {

  /**
   * Cached reverse map: canonical ID -> term ID.
   *
   * @var array|null
   */
  private static ?array $reverseMap = NULL;

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalField(): string {
    return 'canonical_subvention_type';
  }

  /**
   * {@inheritdoc}
   */
  protected function getVocabulary(): string {
    return 'avustuslaji';
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptionValue(string $term_id): ?string {
    $reverse = $this->getReverseMap();
    return $reverse[$term_id] ?? NULL;
  }

  /**
   * Builds a reverse map: term ID -> canonical ID.
   *
   * @return array
   *   Returns the reversed map.
   */
  private function getReverseMap(): array {
    if (self::$reverseMap !== NULL) {
      return self::$reverseMap;
    }

    $reverse = [];
    foreach (CanonicalFields::SUBVENTION_TYPE_MAP as $term_id => $canonical_id) {
      $reverse[(string) $term_id] = (string) $canonical_id;
    }

    return self::$reverseMap = $reverse;
  }

}

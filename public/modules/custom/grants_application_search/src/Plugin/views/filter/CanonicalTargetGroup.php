<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Plugin\views\filter;

use Drupal\views\Attribute\ViewsFilter;

/**
 * Canonical target group filter.
 *
 * @todo UHF-12853: Review this class when removing the webform functionality.
 */
#[ViewsFilter('canonical_target_group_select')]
final class CanonicalTargetGroup extends CanonicalTaxonomySelectFilterBase {

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalField(): string {
    return 'canonical_target_group';
  }

  /**
   * {@inheritdoc}
   */
  protected function getVocabulary(): string {
    return 'target_group';
  }

}

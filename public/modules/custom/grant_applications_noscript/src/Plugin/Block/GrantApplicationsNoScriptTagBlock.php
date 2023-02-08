<?php

namespace Drupal\grant_applications_noscript\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a noscripttag block.
 *
 * @Block(
 *   id = "grant_applications_noscripttag",
 *   admin_label = @Translation("NoScriptTag"),
 *   category = @Translation("Grants")
 * )
 */
class GrantApplicationsNoScriptTagBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
    ];
    return $build;
  }

}

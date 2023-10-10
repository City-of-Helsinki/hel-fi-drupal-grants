<?php

namespace Drupal\grants_preview_link\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a grants Preview Link block.
 *
 * @Block(
 *   id = "grants_preview_link",
 *   admin_label = @Translation("Grants Preview Link"),
 *   category = @Translation("Oma Asiointi")
 * )
 */
class GrantsPreviewLinkBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function build() {

    $allowanceOptions = ['absolute' => TRUE];
    $allowanceUrl = Url::fromRoute('entity.node.canonical', ['node' => 43], $allowanceOptions);
    $allowanceUrl = $allowanceUrl->toString();

    $build = [
      '#theme' => 'grants_preview_link',
      '#allowanceLink' => $allowanceUrl,
    ];
    return $build;
  }

  /**
   * Disable cache.
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

}

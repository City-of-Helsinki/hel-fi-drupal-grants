<?php

namespace Drupal\grants_frontpage_info_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Drupalup Block' Block.
 *
 * @Block(
 *   id = "grants_frontpage_info_block",
 *   admin_label = @Translation("Grants Applications Frontpage Info Block"),
 *   category = @Translation("Hel.fi"),
 * )
 */
class GrantsFrontpageInfoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'grants_frontpage_info_block',
      '#oldSiteUrl' => 'https://asiointi.hel.fi/',
      '#currentApplications' => [$this->t('Education Division, general grant application')],
      '#updatedDate' => "30.3.2023",
    ];
  }

}

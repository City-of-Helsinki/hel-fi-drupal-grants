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
      '#currentApplications' => ['Kasvatus ja koulutus: yleisavustuslomake'],
      '#updatedDate' => "30.3.2023",
    ];
  }

  /**
   * @return int
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * Private function for getting random quote.
   */
  private function getRandQuote() {
    $quotes = [
      '<i>Whoever is happy will make others happy too.</i> Anne Frank',
      '<i>The secret of getting ahead is getting started.</i> Mark Twain',
      '<i>You can\'t blame gravity for falling in love.</i> Albert Einstein',
      '<i>The weak can never forgive. Forgiveness is the attribute of the strong.</i> Mahatma Gandhi'
    ];
    return $quotes[array_rand($quotes)];
  }

}

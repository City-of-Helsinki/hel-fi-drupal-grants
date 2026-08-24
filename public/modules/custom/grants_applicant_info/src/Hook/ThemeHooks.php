<?php

declare(strict_types=1);

namespace Drupal\grants_applicant_info\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for Grants applicant info.
 */
class ThemeHooks {

  /**
   * Implements hook_theme().
   *
   * @return array<string, mixed>
   *   The theme definitions.
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'applicant_info' => [
        'render element' => 'element',
      ],
    ];
  }

}

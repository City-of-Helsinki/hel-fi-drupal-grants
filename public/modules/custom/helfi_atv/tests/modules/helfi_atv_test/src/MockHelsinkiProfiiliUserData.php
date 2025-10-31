<?php

declare(strict_types=1);

namespace Drupal\helfi_atv_test;

use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;

/**
 * Mock helsinki profiili class.
 */
class MockHelsinkiProfiiliUserData extends HelsinkiProfiiliUserData {

  /**
   * Return user roles for testing.
   */
  public function getCurrentUserRoles(): array {
    return ['user', 'helsinkiprofiili'];
  }

  /**
   * Return tokens for unit tests.
   */
  public function getApiAccessTokens(): array {
    return [
      'tokenName' => 'tokenFromMockHelsinkiProfiiliUserData',
    ];
  }

}

<?php

namespace Drupal\grants_metadata_test_webforms;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Mock session for tests.
 */
class MockSession extends Session {

  /**
   * Return id.
   */
  public function getId() {
    return '1234567890123456';
  }

}

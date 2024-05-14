<?php

namespace Drupal\grants_test_base;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Mock session for tests.
 */
class MockSession extends Session {

  /**
   * Return id.
   */
  public function getId(): string {
    return '1234567890123456';
  }

}

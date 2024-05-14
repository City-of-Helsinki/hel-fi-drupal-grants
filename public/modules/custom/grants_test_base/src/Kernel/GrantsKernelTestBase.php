<?php

namespace Drupal\grants_test_base\Kernel;

use Drupal\grants_test_base\MockSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Test base for grants project.
 */
class GrantsKernelTestBase extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();
    // Basis for installing webform.
    $this->installSchema('webform', ['webform']);
    // Install test webforms.
    $this->installConfig(['grants_test_webforms']);
  }

  /**
   * Create session for GrantsProfileService.
   */
  protected function initSession($role = 'registered_community'): void {
    $session = new MockSession();
    \Drupal::service('grants_profile.cache')->setSession($session);
    \Drupal::service('grants_profile.service')->setApplicantType($role);
  }

  /**
   * Load webform based on given id.
   */
  public static function loadWebform(string $webformId) {
    return Webform::load($webformId);
  }

}

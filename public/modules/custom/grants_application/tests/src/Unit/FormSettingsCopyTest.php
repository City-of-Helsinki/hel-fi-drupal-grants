<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\grants_application\Form\FormSettings;
use Drupal\Tests\UnitTestCase;

/**
 * Tests whether applications of a form can be copied.
 *
 * @coversDefaultClass \Drupal\grants_application\Form\FormSettings
 * @group grants_application
 */
final class FormSettingsCopyTest extends UnitTestCase {

  /**
   * Creates form settings.
   *
   * @param array<string, mixed> $settings
   *   The settings that differ from an open, copyable form.
   *
   * @return \Drupal\grants_application\Form\FormSettings
   *   The form settings.
   */
  private function settings(array $settings): FormSettings {
    return new FormSettings($settings + [
      'application_open' => date('Y-m-d\TH:i:s', strtotime('-1 day')),
      'application_close' => date('Y-m-d\TH:i:s', strtotime('+1 day')),
      'continuous' => FALSE,
      'disable_copy' => FALSE,
    ], [], [], []);
  }

  /**
   * Copying requires both copying to be enabled and the form to be open.
   *
   * @covers ::canBeCopied
   */
  public function testCanBeCopied(): void {
    $this->assertTrue($this->settings([])->canBeCopied());
    $this->assertFalse($this->settings(['disable_copy' => TRUE])->canBeCopied());

    $closed = ['application_close' => date('Y-m-d\TH:i:s', strtotime('-1 hour'))];
    $this->assertFalse($this->settings($closed)->canBeCopied());
    $this->assertFalse($this->settings(['application_open' => date('Y-m-d\TH:i:s', strtotime('+1 hour'))])->canBeCopied());

    // A continuous form is always open.
    $this->assertTrue($this->settings($closed + ['continuous' => TRUE])->canBeCopied());
  }

}

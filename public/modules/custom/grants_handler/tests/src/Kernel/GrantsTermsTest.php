<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_handler\Kernel;

use Drupal\block_content\Entity\BlockContent;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Test WebformSubmissionNotesHelper class.
 */
class GrantsTermsTest extends GrantsHandlerKernelTestBase {

  /**
   * Tests terms block rendering.
   */
  public function testGrantsBlock(): void {
    $webform = Webform::create(['id' => 'webform_test', 'title' => 'Test']);
    $webform->save();

    $webform_submission = WebformSubmission::create([
      'id' => 'webform_submission_test',
      'webform_id' => $webform->id(),
      'data' => ['name' => 'John Smith'],
    ]);

    // Test without a valid terms block.
    $variables = [
      'webform' => $webform,
      'webform_submission' => $webform_submission,
    ];
    grants_handler_preprocess_webform_submission_data($variables);

    $this->assertEmpty($variables['terms'] ?? NULL);

    // Create terms block.
    $block = BlockContent::create([
      'type' => 'terms_block',
      'title' => 'Terms of service',
      'langcode' => 'en',
      'body' => 'You are free to...',
      'field_show_on_all_forms' => TRUE,
      'field_link_title' => 'I accept the terms',
    ]);
    $block->save();

    $variables = [
      'webform' => $webform,
      'webform_submission' => $webform_submission,
    ];
    grants_handler_preprocess_webform_submission_data($variables);

    $this->assertNotEmpty($variables['terms']);
    foreach ($variables['terms'] as $terms) {
      $this->assertEquals($terms['#theme'], 'grant_terms');
      $this->assertFalse($terms['#updated_terms']);
    }

    // Test that completed submissions are recognized.
    $webform_submission->setCompletedTime(1735682400);

    $variables = [
      'webform' => $webform,
      'webform_submission' => $webform_submission,
    ];
    grants_handler_preprocess_webform_submission_data($variables);

    $this->assertNotEmpty($variables['terms']);
    foreach ($variables['terms'] as $terms) {
      $this->assertEquals($block->getChangedTime(), $terms['#updated_terms']);
    }
  }

}

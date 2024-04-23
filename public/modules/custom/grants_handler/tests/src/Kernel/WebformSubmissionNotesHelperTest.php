<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_handler\Kernel;

use Drupal\grants_handler\WebformSubmissionNotesHelper;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Test WebformSubmissionNotesHelper class.
 */
class WebformSubmissionNotesHelperTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array<string>
   */
  protected static $modules = ['system', 'webform', 'user', 'field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('webform', ['webform']);
    $this->installConfig('webform');
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');
  }

  /**
   * Get an empty submission object.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   Empty submission object.
   */
  private function getSubmissionObject(): WebformSubmissionInterface {
    $webform = Webform::create(['id' => 'webform_test', 'title' => 'Test']);
    $webform->save();

    // Create webform submission.
    $values = [
      'id' => 'webform_submission_test',
      'webform_id' => $webform->id(),
      'data' => ['name' => 'John Smith'],
    ];
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::create($values);
    return $webform_submission;
  }

  /**
   * Test for getting values.
   */
  public function testSetSingleValue() {
    $submission = $this->getSubmissionObject();
    WebformSubmissionNotesHelper::setValue($submission, 'test_value', 'foo');
    $rawField = $submission->get('notes')->value;
    $testValue = WebformSubmissionNotesHelper::getValue($submission, 'test_value');

    $this->assertEquals($rawField, '{"test_value":"foo"}');
    $this->assertEquals($testValue, 'foo');
  }

  /**
   * Tests for value removals.
   */
  public function testRemoveValue() {
    $submission = $this->getSubmissionObject();
    WebformSubmissionNotesHelper::setValue($submission, 'foo', 'bar');
    WebformSubmissionNotesHelper::setValue($submission, 'user', 'testuser');

    WebformSubmissionNotesHelper::removeValue($submission, 'foo');

    $fooValue = WebformSubmissionNotesHelper::getValue($submission, 'foo');
    $rawField = $submission->get('notes')->value;

    $this->assertEquals($fooValue, NULL);
    $this->assertEquals($rawField, '{"user":"testuser"}');

    WebformSubmissionNotesHelper::removeValue($submission, 'user');
    $userValue = WebformSubmissionNotesHelper::getValue($submission, 'user');
    $rawField = $submission->get('notes')->value;

    $this->assertEquals($userValue, NULL);
    // Removing the last custom value should change field value to null.
    $this->assertEquals($rawField, NULL);
  }

}

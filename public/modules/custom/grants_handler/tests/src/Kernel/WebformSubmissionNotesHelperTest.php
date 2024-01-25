<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_handler\Kernel;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Exception\AmbiguousBundleClassException;
use Drupal\grants_handler\WebformSubmissionNotesHelper;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/** @package Drupal\Tests\grants_handler\Kernel */
class WebformSubmissionNotesHelperTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
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
   * @return WebformSubmissionInterface
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
    return WebformSubmission::create($values);
  }

  public function testSetSingleValue() {
    $submission = $this->getSubmissionObject();
    WebformSubmissionNotesHelper::setValue($submission, 'test_value', 'foo');
    $rawField = $submission->get('notes')->value;
    $testValue = WebformSubmissionNotesHelper::getValue($submission, 'test_value');

    $this->assertEquals($rawField, '{"test_value":"foo"}');
    $this->assertEquals($testValue, 'foo');
  }

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

  public function testNonJsonValue() {
    $submission = $this->getSubmissionObject();
    $submission->set('notes', 'foobar');
    WebformSubmissionNotesHelper::getValue($submission, 'test');

  }


}

